<?php
interface Elluminate_HTML_Output{
   public function getMoodleUrl($url);
   public function getActionIcon($url, $image, $altKey, $attributes = null);
   public function getMoodleImage($image);
}