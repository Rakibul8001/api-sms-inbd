<?php 

//$_SESSION['exclude-csrf'] = true;       use it to exclude from csrf!!

/*
 http://smsinbd.local/api/send-sms?
 api_token=(APITOKEN)
 senderid=(Approved Sender Id)
 message=(Message Content)
 contact_number=(Contact Number)

 //internal API: 2021RrrDTHstDkRnVFkPlPrurhT611DthstInternalAPI
*/


//get delivery status
$app->get('/internal/dl-report', 'DeliveryReportController:updateDeliveryStatus');

$app->get('/internal/campaign-dl-report', 'DeliveryReportController:updateCampaignDelivery');



//home //
$app->get('/', 'HomeController:indexAction')->setName('home');

//for sending sms
$app->get('/sms-api/sendsms', 'SmsApiController:sendSMS')->setName('sendsmsget');
$app->post('/sms-api/sendsms', 'SmsApiController:sendSMS')->setName('sendsms');

//for weblink api
$app->get('/sms-api/sendsms/weblink', 'SmsApiController:sendSMSweblink')->setName('sendsmsget-weblink');
//for divider api
$app->get('/sms-api/sendsms/divider', 'SmsApiController:sendSMSdivider')->setName('sendsmsget-divider');

//  process campaign operator's numbers asynchronously
$app->get('/process-campaign-operatorsms/{id}', 'SmsApiController:processCampaignOperatorsms')->setName('async-sms-sender');

// asynchronous operator processing
$app->get('/internal/process-gp', 'SmsApiController:processAsyncGP');
$app->get('/internal/process-bl', 'SmsApiController:processAsyncBL');
$app->get('/internal/process-robi', 'SmsApiController:processAsyncRobi');
$app->get('/internal/process-ttk', 'SmsApiController:processAsyncTTk');
$app->get('/internal/process-custom', 'SmsApiController:processAsyncCustom');
$app->get('/internal/process-custom2', 'SmsApiController:processAsyncCustom2');
$app->get('/internal/process-custom3', 'SmsApiController:processAsyncCustom3');




// retry pending and failed numbers
$app->post('/sms-api/process-campaign-failed-retry', 'SmsApiController:processCampaignFailedRetry')->setName('retry-sms');

//live status
$app->post('/sms-api/campaign/livestatus', 'SmsApiController:campaignLiveStatus')->setName('campaign-live-status');

// retry pending and failed numbers
$app->post('/sms-api/process-forcerefund', 'SmsApiController:processForceRefund')->setName('force-refund-sms');

//balance check
$app->post('/sms-api/balance', 'SmsApiController:userBalance')->setName('balance');
$app->get('/sms-api/balance', 'SmsApiController:userBalance')->setName('balance');

// sms delivery report API
$app->get('/sms-api/delivery-report', 'DeliveryReportController:getDeliveryReportOfSms');
$app->post('/sms-api/delivery-report', 'DeliveryReportController:getDeliveryReportOfSms');


//upload contacts to group
$app->post('/sms-api/uploadcontacts', 'SmsApiController:uploadContacts');

$app->get('/denied', 'HomeController:accessDeniedAction')->setName('access.denied');


//-------MODEM API--------
//store lowcost sms
$app->get('/sms-api/send-lcsms', 'SmsApiController:storeLcSms');
$app->post('/sms-api/send-lcsms', 'SmsApiController:storeLcSms');

//get sms to send
$app->get('/modem-api/get-sms', 'ModemController:getModemSms');
$app->post('/modem-api/get-sms', 'ModemController:getModemSms');

//push sms result
$app->post('/modem-api/post-status', 'ModemController:postSmsStatus');
$app->get('/modem-api/post-status', 'ModemController:postSmsStatus');

//modem status
$app->post('/modem-api/modem-status', 'ModemController:updateModemStatus');




//------------------- for developement--------------------------------
//API Integration
$app->get('/sms-api/create-ttk', 'ApiIntegrationController:createTtkSenderId');
$app->get('/sms-api/balance-migrate', 'ApiIntegrationController:userBalanceMigrate');
$app->get('/sms-api/balance-migrate-reseller', 'ApiIntegrationController:resellerBalanceMigrate');


//test mnp
$app->get('/test-mnp', 'ApiIntegrationController:testMNP');

//test operator api
$app->get('/test-robi', 'ApiIntegrationController:testRobi');
$app->get('/test-bl', 'ApiIntegrationController:testBL');
$app->get('/test-gp', 'ApiIntegrationController:testGP');
$app->get('/test-metro', 'ApiIntegrationController:testMetro');
$app->get('/test-fusion', 'ApiIntegrationController:testFusion');


$app->get('/test-gp-dl', 'ApiIntegrationController:testGPDelivery');
$app->get('/userbalancefix', 'ApiIntegrationController:usersBalanceCorrection');

//------------------- test api for developement end --------------------------------

//------------------- REFUND BALANCE FIXING ------------------------------

$app->get('/sms-api/fixUserRefund', 'ApiIntegrationController:fixUserRefundBalance')->setName('fixRefundSms');

$app->get('/sms-api/fixUserBalance', 'ApiIntegrationController:fixUserBalance')->setName('fixUserBalance');