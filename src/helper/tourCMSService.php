<?php
namespace TourCMS\OnBoarding\Helper;

use Number;
use PhpParser\Error;
use PhpParser\Node\Expr\Cast\Object_;
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

    #IN PROGRESS
    public function getTourCMSData(string $typeOfData, int $channel = 0, $params = '', int $tour = 0)
    {
        $results = [];
        if ($this->redis->existKey($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour)) {
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
            $results = $this->callTourCMSFunction($typeOfData, $channel, $params, $tour);
            match ($typeOfData) {
                'tours' => $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour => $results["tours"])),
                'booking', 'availability', 'bookingForm', 'bookingScreen', 'bookings', 'bookingErase' => null,
                default => $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour => $results)),
            };
        }
        if (isset($results->error) && $results->error == 'NO MATCHING DATA') {
            throw new Error('No matching data');
        }
        return $results;
    }

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
        };

        return $results;
    }

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

    public function generateNewBooking(int $channel, $params)
    {
        $finalResults = [];
        // Start building the booking XML
        $booking = new SimpleXMLElement('<booking />');

        // Append the total customers, we'll add their details on below
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
            $customer->addChild('title', 'Mr');
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

    public function getBookings(string $params, int $channel)
    {
        $results = $this->list_bookings($params, $channel);
        $availableBookings = new stdClass();
        $cancelledBookings = new stdClass();
        $cancelledBookings->bookings = [];
        $availableBookings->bookings = [];
        foreach ($results->bookings->booking as $booking) {
            $clonedBooking = new stdClass();
            $clonedBooking->cancel_reason = $booking->cancel_reason;
            $clonedBooking->booking_id = $booking->booking_id;
            $clonedBooking->channel_id = $booking->channel_id;
            $clonedBooking->cancel_text = $booking->cancel_text;
            isset($booking->lead_customer_name) ? $clonedBooking->lead_customer_name = $booking->lead_customer_name : '';
            isset($booking->booking_name) ? $clonedBooking->booking_name = $booking->booking_name : '';
            if ($booking->cancel_reason > 0) {
                array_push($cancelledBookings->bookings, $clonedBooking);
            } else {
                array_push($availableBookings->bookings, $clonedBooking);
            }
        }
        return ['availableBookings' => $availableBookings, 'cancelledBookings' => $cancelledBookings];
    }

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
        if ($cancellationResult->error == 'OK') {
            $results = 'Booking ' . $bookingID . ' has been succesfully erased.';
        } else {
            $results = 'Error during cancellation or booking already cancelled.';
        }
        return ['erasingResult' => $results];

    }
}

