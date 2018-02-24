<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Events\Listener\aListener;


class ListenerDispatchResult
    extends aListener
{
    const WEIGHT = ListenerDispatch::WEIGHT - 100;
    const RESULT_DISPATCH = 'result';


    /**
     * @return array|void
     */
    function __invoke($e = null)
    {
        $result = \Poirot\Std\cast($e->collector())->toArray();


        /// With Chains Invokable we can define usable result
        //- return array(
        //-   ListenerDispatch::RESULT_DISPATCH => $r
        //- );
        if ( is_array($result) && isset($result[self::RESULT_DISPATCH]) )
            $result = $result[self::RESULT_DISPATCH];

        // $result that will resolve to SAPI events
        return [ self::RESULT_DISPATCH => $result ];
    }
}
