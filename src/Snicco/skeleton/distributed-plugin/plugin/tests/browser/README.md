# End-to-end/Browser tests

It's recommended that you have some "smoke tests" for your application
that test the application in its entirety.

These tests should prove that you wired your application correctly.
You should never test business logic through end-to-end tests.

There are two options for running end-to-end tests:

1) Using the `WebTestCase` which allows you to emulate a real browser interaction by using a PHP API. 
        
    However, the `WebTestCase` is not performing "real" HTTP requests and will **not** execute Javascript. This method is easier to set up and tests are an order of magnitude faster than option two.
2) Using Codeception's [`WebDriver`](https://codeception.com/docs/modules/WebDriver) module which allows you to make real HTTP requests with a headless Chrome browser. These tests will execute Javascript.
   **Selenium is required** and should run against a server that matches your production environment as close as possible.
   Consult the documentation [here](https://wpbrowser.wptestkit.dev/modules/wpwebdriver) and [here](https://codeception.com/docs/modules/WebDriver).