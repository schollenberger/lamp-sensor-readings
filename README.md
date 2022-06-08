# lamp-sensor-readings

  Web App to run on a Simple-Lamp Docker container which receives and persists sensor readings.

  This project is inspired by:

   - https://github.com/qyjohn/simple-lamp
   - https://github.com/mattrayner/docker-lamp


  Note: It is a bad idea to add the mysql database to git.

  If you want to keep the database in sync with the uploads directory,
  within the container, export the simple-lamp database to the /app directory,
  kill the container, and then commit the changes to the app directory to git.

  To create the WebApp from the git repo, let the container install mysql again
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
