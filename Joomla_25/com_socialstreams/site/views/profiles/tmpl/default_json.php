<?php

defined('_JEXEC') or die('Restricted access');
/*
 * Json Template.
 */

if ($this->items) {

    $response = array();
    foreach($this->items as $item)
        $response[] = $item->profile->display();

    // Get the document object.
    $document = & JFactory::getDocument();

    // Set the MIME type for JSON output.
    $document->setMimeEncoding('application/json');

    // Change the suggested filename.
    JResponse::setHeader('Content-Disposition', 'attachment;filename="' . $this->getName() . '.json"');

    // Output the HTML data.
    echo implode("\n", $response);
}

$app = JFactory::getApplication();
$app->close();
?>
