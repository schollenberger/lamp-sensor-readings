# Curl Commands to Work With a Web Application that Relies on Web Sessions

  When a web app uses a session object to store information like the logged in
  user, it takes a bit more using `curl` to emulate a client using this
  web app.

  Here we assume the PHP way to handle session which is a session cookie in the
  HTTP header in the format of:
    ```
    PHPSESSID=<session-id>
    ```

  Follow these steps to read this cookie and later provide it with subsequent
  CURl commands:

  - First call the basic URL (e.g. `http://localhost/index.php`) with the
    option `-v` and find the session id cookie.
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
    variable. Example that matches the output from above:
    ```
    export curl_session="PHPSESSID=p0iiiqdeo73c4f1lca1rd7l99n"
    ```

  - With this session login into the Web App (this time without verbose) using
    the proper HTTP-POST request.:
    ```
    $ curl  -b $curl_session -X POST -d "username=Test User1" -d "password="***" http://localhost
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
    Check the HTML body that your login succeeded.

  - You are now logged in with this session. Open the home page again and
    check for the difference.
    ```
    $ curl  -b $curl_session -X GET http://localhost/index.html

    ```

 - There are a few REST like commands you can use for administering the
   web app until an admin UI has been implemented. Examples:
   ```
    # Get access token for logged in user
    curl -v -b $curl_session -http://localhost/access_token.php
    # Generate new access token for logged in user
    curl -v -b $curl_session -X POST  http://localhost/access_token.php
   ```
