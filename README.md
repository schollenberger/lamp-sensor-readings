# lamp-sensor-readings

  Web App to run on a Simple-Lamp Docker container which receives and persists
  sensor readings.

  This project is inspired by:

   - https://github.com/qyjohn/simple-lamp
   - https://github.com/mattrayner/docker-lamp

  The documentation here assumes that you run this app using the docker image
  `mattrayner/lamp` and mount the `app` and `mysql` directories from this
  git repo into the container so that the container itself remains stateless.

  Note: It is a bad idea to add the mysql database files to Git!

  If you want to move the database to a different instance, exec into
  the container, export the database `sensor_data`to the `/app` directory,
  kill the container, and then commit the changes to the app directory to Git.

  To create the WebApp from the Git repo, let the container install mysql again
  and then import the database content from within the container.

## Start the container and work with it

  Here we use some environment variables that help you in cut and paste
  the commands below.

  - Start with:
    ```
    # Assuming your current directory is the project dir.
    export dimg="mattrayner/lamp:latest-2004-php8"
    docker run -d --rm -p "80:80" -v ${PWD}//app:/app -v ${PWD}/mysql:/var/lib/mysql ${dimg} | tee docker_cid.tmp
    ```

  - Now you have the container id in a temp file which you can store in
    the shell environment using:
    ```
    export dcid=`cat docker_cid.tmp`
    ```

  - Useful docker commands for this environment:
    ```  
    docker logs  -f $dcid
    docker exec -it $dcid bash
    docker kill $dcid
    docker rm $dcid
    rm docker_cid.tmp
    ```

## First Time Setup

  The ubuntu lamp docker container persist everything in the app and mysql
  directories. The app directory content comes from this repo but you have to
  initialize the database.

  So, after the container comes up for the first time (which installs mysql in
  the mounted mysql directory), you exec a shell inside the container and create
  the sensor readings database.

  In side the container execute:
  ```
  mysql -u root -e "CREATE DATABASE sensor_data;"
  mysql -u root -e "CREATE USER 'username'@'localhost' IDENTIFIED BY 'password';"
  mysql -u root -e "GRANT ALL PRIVILEGES ON sensor_data.* TO 'username'@'localhost';"
  mysql -u username -p sensor_data < sensor_data.sql
    #-> enter "password" as the password
  ```
  In order to restart from scratch, on the host wipe out the directory `mysql`

## Usage

  The URL `http://localhost` redirects you to `index.php` where you can see up
  the last 10 sensor readings from the database.
  You can login as a user (no PW) and add a sensor record manually under your
  user name. After clicking on the link `Back` you then see the new record.

  The URL `http://locahost/phpmyadmin` opens up a the mysql php admin page
  on which you can log in via `username`/`password`.

## Using CURL to add sensor records

  Since of version 3, this app supports Bearer Tokens to authorize for some
  requests (not all), especially to read or add  sensor values using
  `http://localhost/sensor_values.php`.

  For authorization the request either has to contain a valid token of an
  already logged in session or an HTTP Authorization Header bearer token.
  This makes it much easier for IOT or other stateless clients to insert
  sensor values.

  In order to simulate a client that supports sessions with curl, refer to
  document   `Curl-commands-with-web-session.md` in this repo for an example.

  If you want to use an access token, you may retrieve it from the web app.
  As a logged in user (either curl or web browser) enter:
  `http://localhost/access_token.php`.

  It returns the access token of the current user which you set as the bearer
  token in your client code on the IOT device or set it on the shell
  environment using:
  ```
  export token="..."
  ```

  As it is part of the HTTP header, this token works as well in some GET
  requests, e.g. `http://localhost/sensor_values`. Example:
  ```
  curl -b $curl_session -X GET http://localhost/sensor_values.php
  ```
  or
  ```
  curl -H  "Authorization: Bearer $token" -X GET http://localhost/sensor_values.php
  ```

  Assuming you have set a  bearer token, you create a new sensor reading on
  the server by issueing an HTTP-POST request specifying the
  sensor name (`sname`), location (`slocation`) and sensor value (`svalue`).

    Example:
    ```
    curl  -H  "Authorization: Bearer $token" -X POST -d "sname=TstSensor2&slocation=Room2&svalue=9.1" http://localhost/sensor_value.php


    <!-- Page header -->

    <html>
    <head>
      <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
      <title>Sensor Reading Application</title>
    </head>
    <body>

    New sensor value has been added.<br>
      <table width=100% border = 1>
      <tr>
        <td>Username</td>
        <td>Sensor Name</td>
        <td>Location</td><td>Sensor Value</td><
      /tr>
      <tr>
        <td>Demo User</td>
        <td>TstSensor2</td>
        <td>Room2</td>
        <td>9.1</td>
      </tr>
      <table>
    <a href='index.php'>Back</a></body>
    </html>
    ```

  That's it.
