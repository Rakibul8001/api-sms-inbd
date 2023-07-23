<?php

namespace App\Core\UserCountSms;

interface UserCountSms
{

    /**
     * Get total consume mask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function totalConsumeMaskBalance($userid);

    /**
     * Get total consume mask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function resellerTotalConsumeMaskBalance($userid);

    /**
     * Get total consume nonmask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function totalConsumeNonMaskBalance($userid);

    /**
     * Get total consume nonmask sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function resellerTotalConsumeNonMaskBalance($userid);


    /**
     * Get total consume voice sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function totalConsumeVoiceBalance($userid);


    /**
     * Get total consume voice sms balance
     *
     * @param int  $userid
     * @return void
     */
    public function resellerTotalConsumeVoiceBalance($userid);


    /**
     * Get today's consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientConsumeMaskSmsBalance($userid);

    /**
     * Get today's consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientConsumeMaskSmsBalance($userid);

    /**
     * Get today's consume nonmask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientConsumeNonMaskSmsBalance($userid);


    /**
     * Get today's consume nonmask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientConsumeNonMaskSmsBalance($userid);

    /**
     * Get today's consume voice sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientConsumeVoiceSmsBalance($userid);

    /**
     * Get today's consume voice sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientConsumeVoiceSmsBalance($userid);

    /**
     * Get monthly consume mask sms balance
     *
     * @param int $userid
     * @param string $monthname
     * @return void
     */
    public function monthlyConsumeMaskSmsBalance($userid, $monthname=null);

    /**
     * Get monthly consume nonmask sms balance
     *
     * @param int $userid
     * @param string $monthname
     * @return void
     */
    public function monthlyConsumeNonMaskSmsBalance($userid, $monthname=null);

    /**
     * Get monthly consume voice sms balance
     *
     * @param int $userid
     * @param string $monthname
     * @return void
     */
    public function monthlyConsumeVoiceSmsBalance($userid, $monthname=null);

    /**
     * Get yearly consume mask sms balance
     *
     * @param int $userid
     * @param int $yearly
     * @return void
     */
    public function yearlyConsumeMaskSmsBalance($userid, $yearname=null);

    /**
     * Get yearly consume nonmask sms balance
     *
     * @param int $userid
     * @param int $yearly
     * @return void
     */
    public function yearlyConsumeNonMaskSmsBalance($userid, $yearname=null);

    /**
     * Get yearly consume mask sms balance
     *
     * @param int $userid
     * @param int $yearly
     * @return void
     */
    public function yearlyConsumeVoiceSmsBalance($userid, $yearname=null);

    /**
     * Get this week consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientThisWeekConsumeMaskSmsBalance($userid);


    /**
     * Get this week consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientThisWeekConsumeMaskSmsBalance($userid);

    /**
     * Get this month consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function clientThisMonthConsumeMaskSmsBalance($userid);

    /**
     * Get this month consume mask sms balance
     *
     * @param int $userid
     * @return void
     */
    public function resellerClientThisMonthConsumeMaskSmsBalance($userid);

    /**
     * Root user only
     */

     /**
      * Get total sms sent history in current day
      *
      * @return void
      */
    public function todaysSmsSentHistoryForRoot();


    /**
      * Get total sms sent history in current day
      *
      * @return void
      */
      public function resellerClientTodaysSmsSentHistoryForRoot(array $data);


    /**
     * Root user only
     */

     /**
      * Get total sms sent history in current day
      *
      * @return void
      */
      public function thisWeekSmsSentHistoryForRoot();


      /**
      * Get total sms sent history in current weak
      *
      * @return void
      */
      public function resellerThisWeekSmsSentHistoryForRoot(array $data);

      /**
     * Root user only
     */

     /**
      * Get total sms sent history in current day
      *
      * @return void
      */
      public function thisMonthSmsSentHistoryForRoot();

      /**
      * Get total sms sent history in current month
      *
      * @return void
      */
      public function resellerThisMonthSmsSentHistoryForRoot(array $data);
}