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

  As the Web App is using a session object to store the logged in user, it takes
  a bit more using `curl` to emulate an application using this web app.

  - First call the basic URL with the -v option and retrieve the session id.
    Here an example with manually formatted output:
    ```
    $  curl -v http://localhost
    * Rebuilt URL to: localhost/
    *   Trying 127.0.0.1...
    * TCP_NODELAY set
    * Connected to localhost (127.0.0.1) port 80 (#0)
    > GET / HTTP/1.1
    > Host: localhost
    > User-Agent: curl/7.58.0
    > Accept: */*
    >
    < HTTP/1.1 200 OK
    < Date: Wed, 08 Jun 2022 17:05:32 GMT
    < Server: Apache/2.4.41 (Ubuntu)
    < Set-Cookie: PHPSESSID=p0iiiqdeo73c4f1lca1rd7l99n; path=/
    < Expires: Thu, 19 Nov 1981 08:52:00 GMT
    < Cache-Control: no-store, no-cache, must-revalidate
    < Pragma: no-cache
    < Vary: Accept-Encoding
    < Content-Length: 1764
    < Content-Type: text/html; charset=UTF-8
    <

    <html>
      <head>
        <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Sensor Readings Application</title>
      </head>
      <body>
        <table width=100% border=0>
          <tr>
            <td><H1>172.17.0.2</H1></td>
            <td align='right'>
              <form action='index.php' method='post'>Enter Your Name: <br>
                <input type='text' id='username' name ='username' size=20><br>
                <input type='submit' value='login'/>
              </form>
            </td>
          </tr>
        </table>
        <HR>Login to start uploading sensor values.<br>&nbsp;<br>
        Getting latest 10 records from database.<br><br>&nbsp;<br>
        <table width=100% border=1>
        ...
        </table>

        <HR><hr>Session ID: p0iiiqdeo73c4f1lca1rd7l99n
      </body>
    </html>
    ```

  - From this output extract the session ID and store it in an environment
    variable.
    ```
    export curl_session="PHPSESSID=p0iiiqdeo73c4f1lca1rd7l99n"
    ```

  - With this session login into the Web App (this time without verbose):
    ```
    $ curl  -b $curl_session -d "username=Test User1" http://localhost
    <html>
      <head>
        <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Sensor Readings Application</title>
      </head>
      <body>
        <table width=100% border=0>
          <tr>
            <td><H1>172.17.0.2</H1></td>
            <td align='right'>Test User1<br><a href='index.php?logout=yes'>Logout</a></td>
          </tr>
        </table>
        <HR>
        This application displays sensor readings, that you can manually enter
        under your user name.<br>&nbsp;<br><br>
        <form action='sensor_value.php?sensor_read=yes' method='post'>
          <table border=2>
          ...
          </table>
          <input type='submit' value='Go' id='submit_button' name='submit_button' enabled>
        </form>
        Getting latest 10 records from database.<br>
        ...
        <HR>
        <hr>Session ID: gkbdfe96rr61ugh3jsrotk4gcn
      </body>
    </html>
    ```

  - You are now logged in with this session. To add a sensor reading use a post
    request specifying the sensor name (`sname`), location (`slocation`) and
    sensor value (`svalue`).

    Example:
    ```
    curl  -b $curl_session -d "sname=TstSensor2&slocation=Room2&svalue=8.1" http://localhost/sensor_value.php?sensor_read=yes

    <html>
      <head>
        <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Scalable Web Application</title>
      </head>
    <body>


    Hello user [Test User1].<br>
    New sensor value has been added.<br>
    <table width=100% border = 1>
      <tr>
        <td>Username</td><td>Sensor Name</td><td>Location</td><td>Sensor Value</td>
      </tr>
      <tr>
        <td>Test User1</td><td>TstSensor2</td><td>Room2</td><td>8.1</td>
      </tr>
    <table>
    <a href='index.php'>Back</a></body>
    ```

  That's it.
