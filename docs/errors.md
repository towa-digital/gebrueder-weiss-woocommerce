# Errors

This section provides an overview of possible errors and how to deal with them. Errors can be caused either through the API itself, or via callbacks. 

## API

|  Error | Error Message | Status Quo   | Recommended Action   | 
|---|---|---|---|
| ERROR IN POST-REQUEST   |Bad Request (400): Error in request, invalid entry for required field, invalid ID.   |  Error is caught and order is logged. Failed orders are retransmitted multiple times until the request is successful or failed twice. If successful, the status of the failed order is set to "Success" in the log. If the order was not placed successfully, we could set the status of the order to the error status defined by the customer and send an email to the Wordpress admin with the order ID and the error. |  No action recommendation. |
| |  Unauthorized (401): Error during authorization, access token has expired, access rights to the API are missing. | See above.  |  No action recommendation. |
|   | Conflict (409): An order with this ID already exists. | The error is caught, the order is set to the error status defined by the customer and an email is sent to the Wordpress admin. No attempt will be made to resubmit the order.  |  No action recommendation. |
| CAN NOT REACH SERVER  |  Internal Server Error (500, 5xx). The server cannot be reached for unspecified reasons.  | See Status Quo 404. Only the standard error message is issued. | Transmission of the error message also to Gebrüder Weiss (by mail), as the problem is related to the LogisticsAPI server.  | 
| |Service unavailable (503): Server is overloaded or cannot be reached due to maintenance work.  |  See above. | Transmission of the error message also to Gebrüder Weiss (by mail), as the problem is related to the LogisticsAPI server.  |
|   | Gateway Timeout (504): No response from proxy server.  | See above.  | Transmission of the error message also to Gebrüder Weiss (by mail), as the problem is related to the LogisticsAPI server.  |
| OTHER ERROR MESSAGES  | Redirect (3xx): The resource is no longer available at this address, a proxy server is required, etc. |  See above. | Transmission of the error message to Gebrüder Weiss as well, since the customer is not responsible for the error.  |
|   | Client Error (4xx):  The resource could not be reached and either the resource is unavailable or the request contains bad syntax. |  See above. | Define a scheme for response codes, for which Gebrüder Weiss should also be informed by email.  |
| REQUEST-THROTTLING   | Too many Exceptions (429): The request limit was reached (1000/minute).  | See above. |  If required, the request limit must be increased to allow a larger number of requests. |
|  ERROR In GET-REQUEST  |  Bad Request (400): The server cannot or will not process the request due to something that is perceived to be a client error.  | See above.  |  Errors should be caught in the same way as with post requests and an error message should be issued in the event of errors and, analogously to post requests, an email with the ID of the order and the error message should be sent to the WP admin or Gebrüder Weiss. |
|    |  Unauthorized (401): Error during authorization.  | See above.  |  Errors should be caught in the same way as with post requests and an error message should be issued in the event of errors and, analogously to post requests, an email with the ID of the order and the error message should be sent to the WP admin or Gebrüder Weiss. |
|    |  Not Found (404): Order with this id was not found.  | See above.  |  Errors should be caught in the same way as with post requests and an error message should be issued in the event of errors and, analogously to post requests, an email with the ID of the order and the error message should be sent to the WP admin or Gebrüder Weiss. |


## Callback

|  Error | Error Message | Status Quo   | Recommended Action   | 
|---|---|---|---|
| CALLBACK WILL NOT BE SENT  |Callback server not available, error creating callback.   |  Currently not covered. |  In the plugin: logging of successful requests and querying the status at regular intervals, mail to the WP admin if the status has not been updated even after repeated queries, so that it can be queried at Gebrüder Weiss. At GW: Logging of callbacks and comparison of the statuses of orders for which a callback should be triggered. |
| BAD CALLBACK| Wrong ID is returned, etc.  | Currently not covered.  |  See above. |
| PROCESSING OF THE ORDER  | Required data is missing.  | If necessary, the error will be corrected in consultation with the customer. If the error cannot be corrected, the order is canceled and a corresponding callback is triggered, which sets the status of the order to error status.  | Extension of the status quo to include the above recommendation so that the callback for a failed order is also covered.  |