How to Install SuccessWhale
---------------------------

To run your own copy of SuccessWhale, unpack the ZIP file or clone the github repository onto a web server that supports PHP and MySQL.  It also supports running the PHP side of things on Heroku, though you will have to bring your own MySQL server.

Once you have your web server, PHP and MySQL set up, rename the `sample.env`  to `.env`, and start editing it. As explained in the file, you'll need a variety of bits of information to get SuccessWhale up and running.

Each service that SuccessWhale supports has a "x_ENABLED" parameter that sets whether or not SuccessWhale expects to use it.  You'll need to enable at least one core service (e.g. Twitter, Facebook) to make your SuccessWhale clone useful.  As well as setting ENABLED to true, enter the other required information (this will be given to you when you register your new app with Twitter/Facebook/whatever).

You must also enter details for a MySQL database. The database will be used to remember users’ configured accounts and column setup, will cache expanded links (speeding up loads somewhat), and will allow users to cache their authentication tokens (for example, users will be able to log in with a SuccessWhale password and have SuccessWhale manage their authentication with Twitter and Facebook.) The app will create DB tables for you when you visit index.php for the first time.

Google Analytics is also supported, and you can enter your ID in `.env`.

The final setting is DEBUG - enable this to get printouts of various errors you may encounter when setting up the software, but remember to disable it again before going live.

Once `.env` is set up just navigate to index.php, log in with your service of choice, and you should be up and running!

*If you want to run SuccessWhale on Heroku, once your `.env` file is set up, run the following commands: `heroku create`, `git push heroku master`, `heroku plugins:install git://github.com/ddollar/heroku-config.git`, `heroku config:push`.*

Please see the file README.md for more information.
