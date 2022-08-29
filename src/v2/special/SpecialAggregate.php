<?php

namespace sinri\ark\database\sqlite\v2\special;
/**
 * @since 2.0
 */
interface SpecialAggregate
{
    /**
     * Name of the SQL aggregate to be created or redefined.
     * @return string
     */
    public function getName(): string;

    /**
     * Callback function called for each row of the result set.
     * Your PHP function should accumulate the result and store it in the aggregation context.
     * @param mixed $context `null` for the first row; on subsequent rows it will have the value that was previously returned from the step function; you should use this to maintain the aggregate state.
     * @param int $rowNumber The current row number.
     * @param mixed $value The first argument passed to the aggregate.
     * @param mixed ...$values Further arguments passed to the aggregate.
     * @return mixed The return value of this function will be used as the context argument in the next call of the step or finalize functions.
     */
    public function stepCallback(
        $context,
        int $rowNumber,
        $value,
        ...$values
    );

    /**
     * Callback function to aggregate the "stepped" data from each row.
     * Once all the rows have been processed,
     *  this function will be called,
     *  and it should then take the data from the aggregation context and return the result.
     * This callback function should return a type understood by SQLite (i.e. scalar type).
     * @param mixed $context Holds the return value from the very last call to the step function.
     * @param int $rowNumber Always 0.
     * @return mixed The return value of this function will be used as the return value for the aggregate.
     */
    public function finalCallback($context, int $rowNumber);

    /**
     * The number of arguments that the SQL aggregate takes.
     * If this parameter is negative, then the SQL aggregate may take any number of arguments.
     * @return int
     */
    public function getArgumentCount(): int;
}