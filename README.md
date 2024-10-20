Interview Test - Fee Calculation
=====

# Finshed task details

Used PHP 8.3 and phpunit library for tests

I used my own dir structure for this task.

Use composer install (obvoiusly) to install dependencies

index.php file is in /src directory which has main code

Example GET request:

http://localhost:8000/src/?term=24&amount=11500 - tested on built in PHP server.

which should respond with json:
{
    "fee": 460
}
Tests are in tests/Unit/FeeCalculatorTest.php file

I did not use DTO for request (there is basic sanitization of GET data).

I created additional Model for Bounds.

There is very basic exception handling.

No feature branching (except rc to master) was used.



