# - On Boarding Belén - Set up

### Index

1. Previous steps
2. Downloading and installing the project
3. Setting up 


## Previous steps 
- First you should make sure you have installed and configured:
    - Git
    - Docker
    - The main Palisis repositories (such as tourcms_core, api , etc ... ) 

## Downloading the project
- Download the project from:
```
https://github.com/palisis-juanra/onboardingBelen
```
- Next move the project to your Palisis projects folder


## Setting up
1.  Configure the .default file located at onboardingBelen/config/.default and change it's name to .env:
```
cd  YOUR_PATH/onboardingBelen/config;
nano .default;
mv .default .env
```
2. Configure the onboardingBelendefault.conf file located at onboardingBelen/config and move it to the apache/sites folder in your tourcms docker folder
```
cd  YOUR_PATH/onboardingBelen/config;
nano onboardingBelendefault.conf;
mv YOUR_PATH/onboardingBelen/config/onboardingBelendefault.conf YOUR_PATH/apache/sites/onboardingBelen.conf;

```

3. Open a terminal in the Palisis docker folder and run:
```
docker compose down; #In case that the Palisis docker was already running
docker compose build; 
docker compose up;
```
4. Open a terminal in the docker apache container and move to the onboardingBelen project directory
```
docker exec -it YOUR_DOCKER_CONTAINER_NAME /bin/bash;
cd YOUR_PATH/onboardingBelen;

```
5. Once in run:
```
composer install;
```


#### Belén Ruiz Juárez - Palisis 2025


