 FCIT Scedule Bot
-------------------
#### 1. How does It process messages?

The main file in a whole project is _bot.php_, It responsive for processing messages and rules with the main logic, also there included all required files and libs. 
Method _on()_ used to listen to all incoming messages and ignore these one which start with "/" symbol to avoid double processing of commands (because there supposed to be some separate methods for command processing and thus If we won't filter It then It'll be processed with both of _on()_ and _command()_ methods).

##### 1.1. router() method 

This method supposed to determine a method which should process the message. For this purpose It should follow these steps:
* Look for User's Telegram ID in the Database with method _getUserNavState($userid)_ of database wrapper
* Once It finds User with such Telegram ID It returns this User's current Navigation State(?) integer and according to It determines which method should process message
* If user was not found then system writes data about current one and make It's state equal _0_ 