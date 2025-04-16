<?php
namespace TourCMS\OnBoarding\Helper;

use SimpleXMLElement;
use stdClass;
use TourCMS\Utils\TourCMS;
use TourCMS\OnBoarding\Helper\redisWrapper;
use TourCMS\OnBoarding\Config\env;


class tourCMSService extends TourCMS
{

    protected $redis;
    public $channel_id;
    protected $base_url;

    public function __construct(string $type)
    {
        $api_key = env::getEnvVariable("API_KEY");
        $type == 'o' ? $marketplace_id = env::getEnvVariable("MARKETPLACE_ID_OPERATOR") : $marketplace_id = env::getEnvVariable("MARKETPLACE_ID_AGENT");
        parent::__construct($marketplace_id, $api_key, 'simplexml', 0);
        $this->base_url = 'http://api.tourcms.local';
        $this->redis = new redisWrapper();
        $this->channel_id = env::getEnvVariable("CHANNEL_ID");
    }

    # This function manages the data from TourCMS and stores it on Redis
    # Right now the data stored in redis are: channels, channels tours and individual tour info. 
    # If the data has been stored by the user before it will be recovered from Redis and not from the API.
    public function getTourCMSData(string $typeOfData, int $channel = 0, $params = '', int $tour = 0)
    {
        // An array to store the data is created
        $results = [];

        // Based on the type of data requested and of the user session stored we check in redis if the data
        // has been already stored before
        if ($this->redis->existKey($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour)) {

            // If the tours data is required we will also retrieve the channels 
            // This is needed as the tours template needs it to be able to show 
            // a dropdown menu of the channels
            // By default the only data that we will retrieve is the one asked for
            switch ($typeOfData) {
                case 'tours':
                    $encodedTours = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour, 'string');
                    $encodedChannels = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'] . 'channels00', 'string');
                    $results = ['tours' => json_decode($encodedTours, true), 'channels' => json_decode($encodedChannels)];
                    break;

                default:
                    $encodedResults = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour, 'string');
                    $results = json_decode($encodedResults, true);
                    break;
            }
        } else {

            // We call our TourCMS handler and store the retrieve data in Redis
            $results = $this->callTourCMSFunction($typeOfData, $channel, $params, $tour);
            match ($typeOfData) {
                'tours' => $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour => $results["tours"])),
                'booking', 'availability', 'bookingForm', 'bookingScreen', 'bookings', 'bookingErase', 'customerBooking', 'customers', 'customerUpdateResult' => null,
                default => $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour => $results)),
            };
        }
        return $results;
    }

    // This function manages the calls to TourCMS depending of the kind of data needed
    public function callTourCMSFunction($typeOfData, int $channel = 0, $params = '', int $tour = 0)
    {
        $results = [];
        match ($typeOfData) {
            'channels' => $results = $this->list_channels($params),
            'tours' => $results = $this->channelTours($channel, $params),
            'booking' => $results = $this->show_tour($tour, $channel),
            'tour' => $results = $this->show_tour($tour, $channel),
            'availability' => $results = $this->check_tour_availability($params, $tour, $channel),
            'bookingScreen' => $results = $this->generateNewBooking($channel, $params),
            'customerBooking' => $results = $this->show_booking($params, $channel),
            'bookings' => $results = $this->getBookings($params, $channel),
            'bookingErase' => $results = $this->bookingErase($params, $channel),
            'customers' => $results = $this->show_customer($params, $channel),
            'customerUpdateResult' => $results = $this->updateCustomer($params, $channel),
        };

        return $results;
    }

    // This function gets the tours for the chosen channel and all the channels 
    public function channelTours(int $channel = 0, $params = '')
    {
        $tours = $this->list_tours($channel);
        $channels = null;
        if ($this->redis->existKey($_COOKIE['PHPSESSID'] . 'channels00')) {
            $encodedResults = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'] . 'channels00', 'string');
            $channels = json_decode($encodedResults, true);
        } else {
            $channels = $this->callTourCMSFunction('channels');
            $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . 'channels00' => $channels));
        }
        $results = ["tours" => $tours, "channels" => $channels];
        return $results;
    }

    // This function generates a new booking based in the user data
    public function generateNewBooking(int $channel, $params)
    {
        $finalResults = [];
        // Start building the booking XML
        $booking = new SimpleXMLElement('<booking />');

        // Append the total customers, we'll add their details on below
        // Right now the form can only take 1 customer so we will insert only 
        // 1 customer by default
        $booking->addChild('total_customers', '1');

        // Append a container for the components to be booked
        $components = $booking->addChild('components');

        // Add a component node for each item to add to the booking
        $component = $components->addChild('component');

        // "Component key" obtained via call to "Check availability"
        $component->addChild('component_key', $params['component_key']);

        // Append a container for the customer recrds
        $customers = $booking->addChild('customers');
        $customer = $customers->addChild('customer');
        $customer->addChild('firstname', $params['firstname']);
        $customer->addChild('surname', $params['surname']);
        if (isset($params['title'])) {
            $customer->addChild('title', $params['title']);
        }
        $customer->addChild('email', $params['email']);

        // Query the TourCMS API, creating the booking
        $result = $this->start_new_booking($booking, 142);

        if (isset($result) && $result->booking) {
            // Temporary booking ID (obtained via "Start booking")
            $booking_id = $result->booking->booking_id;

            // Channel the booking is made with
            $channel = $result->booking->channel_id;

            // Build the XML to post to TourCMS
            $booking = new SimpleXMLElement('<booking />');
            $booking->addChild('booking_id', $booking_id);

            // Query the TourCMS API, upgrading the booking from temporary to live
            $result = $this->commit_new_booking($booking, $channel);
            if ($result->error == "OK") {
                $finalResults = ['booking' => $result->booking];
            }
        }
        return $finalResults;
    }

    // This function gets all the bookings and classifies them
    // in available and cancelled
    public function getBookings(string $params, int $channel)
    {
        // List of bookings from live
        $results = $this->list_bookings($params, $channel);

        // Start creating the objects to store the classified bookings and their properties 
        $availableBookings = new stdClass();
        $cancelledBookings = new stdClass();
        $cancelledBookings->bookings = [];
        $availableBookings->bookings = [];

        // Start iterating over the booking list
        foreach ($results->bookings->booking as $booking) {

            // Now we will clone each booking and it's desired properties to show later on 
            $clonedBooking = new stdClass();
            $clonedBooking->cancel_reason = $booking->cancel_reason;
            $clonedBooking->booking_id = $booking->booking_id;
            $clonedBooking->channel_id = $booking->channel_id;
            $clonedBooking->cancel_text = $booking->cancel_text;
            isset($booking->lead_customer_name) ? $clonedBooking->lead_customer_name = $booking->lead_customer_name : '';
            isset($booking->booking_name) ? $clonedBooking->booking_name = $booking->booking_name : '';

            // If the cancel reason is more than 0 we will save the booking as cancelled
            if ($booking->cancel_reason > 0) {
                array_push($cancelledBookings->bookings, $clonedBooking);
            } else {
                array_push($availableBookings->bookings, $clonedBooking);
            }
        }
        // Return the cloned bookings now classified
        return ['availableBookings' => $availableBookings, 'cancelledBookings' => $cancelledBookings];
    }

    // This function cancels the chosen booking from live
    public function bookingErase(int $bookingID, int $channel)
    {
        // Create a new SimpleXMLElement to hold the booking details
        $booking = new SimpleXMLElement('<booking />');

        // Must set the Booking ID on the XML, so TourCMS knows which to cancel
        $booking->addChild('booking_id', $bookingID);
        $booking->addChild('note', 'Booking eliminated by user');

        // Call TourCMS API, cancelling the booking
        $cancellationResult = $this->cancel_booking($booking, $channel);
        $results = '';

        // Creates the appropriate message depending of the cancellation operation result
        if ($cancellationResult->error == 'OK') {
            $results = 'Booking ' . $bookingID . ' has been successfully erased.';
        } else {
            $results = 'Error during cancellation or booking already cancelled.';
        }

        // Return an array containing the cancellation result message
        return ['erasingResult' => $results];
    }

    //This function updates the info of the customer 
    public function updateCustomer($params, int $channel)
    {
        // Starts by creating an Simple XML of the customer
        $customer = new SimpleXMLElement('<customer />');

        // Must set the Customer ID on the XML, so TourCMS knows
        // who to update
        $customer->addChild('customer_id', $params['customer_id']);
        $values = ['firstname', 'surname', 'title', 'email', 'dob', 'agecat_text', 'pass_num', 'country_text'];
        // Append a container for the customer records
        foreach ($values as $value) {
            if (isset($params[$value]) && $params[$value] !== "") {
                $customer->addChild($value, $params[$value]);
            }
        }
        // Call TourCMS API, updating the customer
        $result = $this->update_customer($customer, $channel);

        // Check the result, will be "OK" if a customer was updated
        if (isset($result)) {
            switch ($result->error) {
                case "OK":
                    // Print a success message
                    $result = "Thanks, your details have been updated";
                    break;
                case "NO DATA CHANGED":
                    // Nothing was changed, old data matched new data
                    $result = "Thanks, it looks like we have your correct data already!";
                    break;
                default:
                    // Some other problem (could check error to see what)
                    $result = "Sorry, unable to update your details at this time";
                    break;
            }
        } else {
            $result = "Connection failed with our servers. Please try again later.";
        }

        // Returns the customer update operation result message
        return ['updatingResult' => $result];
    }
}