<?php

/**
 * Validate a field input
 *
 * @param array $data
 * @param bool $redirect
 * @return array
 */
function _validation(array $data, bool $redirect = true) {

    $state = [];

    foreach ($data as $key => $value) {

        $state = _validator($key, $value, $state);

    }

    if (array_key_exists('errors', $state) && $redirect) {

        // Set state in session before redirect
        $_SESSION['flash']['errors'] = $state['errors'];
        $_SESSION['flash']['old'] = $state['values'];

        _redirect_back();
    }

    return $state;
}

/**
 * Validate multiple field inputs
 *
 * @param $input
 * @param $rules
 * @param array $state
 * @param bool $redirect
 * @return array
 */
function _validator($input, $rules, array $state, $redirect = false)
{
    if (! array_key_exists($input, $_POST)) {
        die ($input.' was not found in the posted data.');
    }

    $rules = explode('|',$rules);

    $fails = null;

    foreach ($rules as $rule) {

        if (preg_match("/:/", $rule)) {

            $arguments = explode(':', $rule);

            $appendRule = "_validation__rule_{$arguments[0]}";

            $result = call_user_func($appendRule, $input, $arguments[1]);

            if ($result['result'] === false) {
                $fails = $result['message'];
                break;
            }

        } else {

            $appendRule = "_validation__rule_{$rule}";

            $result = call_user_func($appendRule, $input);

            if ($result['result'] === false) {
                $fails = $result['message'];
                break;
            }
        }

    }

    $state['validated'][$input] = $result['value'][$input];

    if (! empty($fails) && $redirect === false) {

        $state['errors'][$input] = $fails;

        return $state;

    } elseif (! empty($fails) && $redirect === true) {

        $state['errors'][$input] = $fails;

        // Set state in session before redirect
        $_SESSION['errors'] = $state['errors'];
        $_SESSION['old'] = $state['values'];

        _redirect_back();
    } else {

        return $state;
    }
}

/**
 * Validates if the given input is not empty or in other words is input mandatory and
 * required.
 *
 * @param string $input
 * @return array
 */
function _validation__rule_required($input)
{
    $value = $_POST[$input];

    if (is_string($value)) {
        $value = trim($value);
    }

    if (!empty($value)) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => str_replace('_', ' ', $input) . ' field cannot be empty.'];

    }
}

/**
 * Validates an email address.
 *
 * @param string $input
 * @return array
 */
function _validation__rule_email($input)
{
    $value = $_POST[$input];

    if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => 'Invalid email address.'];

    }
}

/**
 * Validates if the input is equal to some value.
 *
 * @param $input
 * @param $compare_to
 * @return array
 */
function _validation__rule_equals($input, $compare_to)
{
    $value = $_POST[$input];

    return $value == $_POST[$compare_to] ? ['result' => true, 'value' => [$input => $value]] : [
        'result' => false,
        'value' => [$input => $value],
        'message' => "{$input} did not match ". str_replace('_', ' ', $compare_to) ."."];
}

/**
 * Validates if the input is greater than the minimum value.
 *
 * @param string $input
 * @param integer $min
 * @return array
 */
function _validation__rule_min($input, $min) {
    $value = $_POST[$input];

    if (strlen($value) >= (int) $min) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => str_replace('_', ' ', $input) . " should not be less than {$min} characters."];

    }
}

/**
 * Validates if the input doesn't exceed the maximum value.
 *
 * @param $input
 * @param $min
 * @return array
 */
function _validation__rule_max($input, $min)
{
    $value = $_POST[$input];

    if (strlen($value) <= (int) $min) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => str_replace('_', ' ', $input) . " should not be exceed {$min} characters."];

    }
}

/**
 * Validates if the given input already exists in the database.
 *
 * @param string $input
 * @param string $table
 * @return array
 */
function _validation__rule_unique($input, $table)
{
    $value = $_POST[$input];

    $result = db__read($table, [[$input, '=', $value]]);

    if($result !== null) {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => "This {$input} already exist."];

    } else {

        return ['result' => true, 'value' => [$input => $value]];
    }
}

/**
 * @param $input
 * @param $table
 * @return array
 */
function _validation__rule_exists($input, $table) {
    $value = $_POST[$input];

    $result = db_read($table, [[$input, '=', $value]]);

    if($result === null) {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => "This {$input} does not exist."];

    } else {

        return ['result' => true, 'value' => [$input => $value]];
    }
}

/**
 * Validates if the given input is of type string.
 *
 * @param string $input
 * @return array
 */
function _validation__rule_string($input)
{
    $value = $_POST[$input];

    if (is_string($value)) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => $input .' is not of type string.'];

    }
}

/**
 * Validates if the given input is of type integer.
 *
 * @param string $input
 * @return array
 */
function _validation__rule_int($input)
{
    $value = $_POST[$input];

    if (is_integer($value)) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => $input .' is not of type integer.'];

    }
}

/**
 * Validates if the given input is a valid url.
 *
 * @param string $input
 * @return array
 */
function _validation__rule_url($input)
{
    $value = $_POST[$input];

    if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $value)) {

        return ['result' => true, 'value' => [$input => $value]];

    } else {

        return [
            'result' => false,
            'value' => [$input => $value],
            'message' => $input .' is not a valid url.'];

    }
}
