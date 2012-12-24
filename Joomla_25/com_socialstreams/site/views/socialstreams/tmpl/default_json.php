<?php

defined('_JEXEC') or die('Restricted access');
/*
 * Json Template.
 */

    // Get the document object.
    $document = & JFactory::getDocument();

    // Set the MIME type for JSON output.
    $document->setMimeEncoding('application/json');

    // Change the suggested filename.
    JResponse::setHeader('Content-Disposition', 'attachment;filename="' . $this->getName() . '.json"');

    // Output the JSON data.
    echo json_encode($this->response);

$app = JFactory::getApplication();
$app->close();
?>
