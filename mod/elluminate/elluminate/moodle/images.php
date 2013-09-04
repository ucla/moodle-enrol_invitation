<?php
/**
 * With moodle 2.4, SVG icons were introduced.  The best way to access
 * these is to use the moodle output rendering API in the following way:
 * 
 * $OUTPUT->pix_url('delete','elluminate');
 * 
 * This will make moodle generate a URL like: 
 * 
 * /theme/image.php/standard/elluminate/1355941418/delete
 * 
 * which looks in mod/elluminate/pix/ and returns either the SVG, PNG or GIF 
 * file (whichever is available), and as a bonus caches the file as well.
 * 
 * I've moved this into a class so that we centralize the number of locations
 * in our module that make use of the $OUTPUT rendering library in case they
 * change at some point.
 * 
 * @author dwieser
 *
 */
class Elluminate_Moodle_Images{
   
   const MODULE_NAME = 'elluminate';
   
   public function getImageUrl($imagename){
      global $OUTPUT;
      return $OUTPUT->pix_url($imagename,self::MODULE_NAME);
   }
}