<?php
require 'includes/travel-idea-details-data.php';
foreach (array('everest-base-camp','pokhara-lakeside','kathmandu-heritage') as $slug) {
    if (isset($travel_idea_details[$slug])) {
        foreach ($travel_idea_details[$slug]['itinerary'] as $day => $info) {
            echo $slug . ' ' . $day . ' ' . (isset($info['img']) ? $info['img'] : 'NULL') . "\n";
        }
    }
}
