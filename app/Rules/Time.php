<?php

namespace Challengr\Rules;

use Illuminate\Contracts\Validation\Rule;

class Time implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/^((?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$)/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please enter a valid time (hours:minutes:seconds).';
    }
}
