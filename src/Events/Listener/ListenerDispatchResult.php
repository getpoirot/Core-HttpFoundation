<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Events\EventHeap;
use Poirot\Events\Listener\aListener;
use Poirot\Std\Type\StdTravers;


class ListenerDispatchResult
    extends aListener
{
    const WEIGHT = ListenerDispatch::WEIGHT - 100;
    const RESULT_DISPATCH = 'result';


    /**
     * @param EventHeap $e
     *
     * @return array|void
     */
    function __invoke($e = null)
    {
        $result = StdTravers::of( $e->collector() )
            ->toArray();


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
