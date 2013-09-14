<?php
/* This interface is a helper for the SAS SOAP implementation.  It's purpose is to take
 * API responses from the Web Service and translate them into the required business
 * objects for the rest of the system.
 *
 * Above the level of Elluminate_WS_SchedulingManager, there should be no knowledge of API
 * implementation, and the system should only work with the business objects.
*/
interface Elluminate_WS_APIResponseHandler{
   public function processResponse($apiResponse);
}