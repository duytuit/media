<?php

function link_image_big($media_file = '')
{
    return $media_file;
}
function pushNotification($exception)
{
    if (method_exists($exception, 'getStatusCode')) {
        $statusCode = $exception->getStatusCode();
    }else{
        $statusCode = 200;
    }
    if (in_array($statusCode, [404, 405, 401])
        || in_array(@$_SERVER['HTTP_USER_AGENT'], ['TelegramBot (like TwitterBot)'])
        || in_array($exception->getMessage(), [
            'Unauthenticated.', 'The given data was invalid.', 'CSRF token mismatch.'
        ])
        || $exception->getLine() == 1
    ) {
        return false;
    }
    $link_issue ='';
    if (app()->bound('sentry')) {
        $sentry_id = app('sentry')->captureException($exception);
        $link_issue = config('app.sentry_debug_query_url') . $sentry_id;
    }
    $msg = "Link issues: " . $link_issue;
    $msg .= "\nMessage: " . $exception->getMessage();
    $msg .= "\nStatusCode: " . $statusCode;
    $msg .= "\nFile: " . $exception->getFile() . ':' . $exception->getLine();
    $msg .= "\nREMOTE_ADDR: " . @$_SERVER['REMOTE_ADDR'];
    $msg .= "\nHTTP_USER_AGENT: " . @$_SERVER['HTTP_USER_AGENT'];
    $msg .= "\nHTTP_REFERER: " . @$_SERVER['HTTP_REFERER'];
    $msg .= "\nFULL_URL: " . request()->fullUrl();
    $msg .= "\nREQUEST_METHOD: " . @$_SERVER['REQUEST_METHOD'];
    $msg .= "\nSERVER_NAME: " . @$_SERVER['SERVER_NAME'];
    $msg .= "\nHTTP_HOST: " . @$_SERVER['HTTP_HOST'];
    $msg .= "\nREQUEST_URI: " . @$_SERVER['REQUEST_URI'];

    $link = 'https://api.telegram.org/bot1999108268:AAEurRUBb2S2fK7lG7NS1M7u3oVNLO10_No/sendMessage?chat_id=-717344824&text=';
    //notif_one_id
    $ch = curl_init();
    // Set the URL
    curl_setopt($ch, CURLOPT_URL, $link . urlencode($msg));
    // Removes the headers from the output
    curl_setopt($ch, CURLOPT_HEADER, 0);
    // Return the output instead of displaying it directly
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Execute the curl session
    curl_exec($ch);
    // Close the curl session
    curl_close($ch);
    // Return the output as a variable
    return true;
}