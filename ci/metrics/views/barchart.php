<?php

# Code adapted from: http://www.zend.com/zend/spotlight/code-gallery-wade1.php

/* Input should be in the form of:
* ?title=My%20Bar%20Graph&width=500&data=Item%201^50^^Item%202^60^^Item%203^20^^
* */
/* Put the input parameters
* into nicer variables */
$title = isset($title) ? urldecode($title) : "";
$data  = isset($data) ? urldecode($data) : "";
$width = isset($width) ? $width : "";
$percent = isset($percent) ? $percent : "";
$y_axis_label = isset($ylabel) ? $ylabel : "";
$mode = isset($mode) ? $mode : "";
$label_width = isset($labelWidth) ? $labelWidth : "";

/* Start positions of graph */
/* - where to start the bars */

$x = $label_width + 25;
#70;
$y = 30;
$right_margin = 80;
$bar_width = 10;
$total = 0; /* initialize variable */
$unit = (($width - $x) - $right_margin) / 100;
$items = explode("^^", $data);
/* Calculate total of data */
if ($mode=="aggregate")
{
while (list($key,$item) = each($items)) {
    if ($item) {
        $pos   = strpos($item, "^");
        $value = substr($item, $pos + 1, strlen($item));
        $total = $total + $value;
    }
}
}
else if ($mode=="percentage")
{
$total=100; # For percentages.
}
else if ($mode=="maximum")
{
$total=0;
while (list($key,$item) = each($items)) {
    if ($item) {
        $pos   = strpos($item, "^");
        $value = substr($item, $pos + 1, strlen($item));
	if ($total < $value) { $total=$value; }
    }
}
if ($total == 0) {$total=1;}
}

reset ($items);
/* Calculate height of graph */
$height = sizeof ($items) * ($bar_width + 20);
header("Content-type: image/png");
$im = ImageCreate($width, $height);

/* Allocate colors
$labels = ImageColorAllocate($im, 240, 240, 70);
$bgcolor= ImageColorAllocate($im, 0, 64, 128);
$text  = ImageColorAllocate($im, 255, 255, 255);
$bar    = ImageColorAllocate($im, 64, 100, 168);
*/

$text  = ImageColorAllocate($im, 0, 0, 0);
$labels = ImageColorAllocate($im, 0, 0, 0);
$bgcolor= ImageColorAllocate($im, 220, 220, 220);
$bar    = ImageColorAllocate($im, 0, 64, 128 );

/* Background */
ImageFilledRectangle($im, 0, 0, $width, $height, $bgcolor);
/* Title */
$title_x = (imagesx($im)- 7.5 * strlen($title)) / 2;
ImageString($im, 3, $title_x, 4, $title, $text);

/* Y axis label */

ImageStringUp($im, 3, 5, 
	($height + (imagefontwidth(3)*(strlen($y_axis_label))))/2 , 
	$y_axis_label, $text);

/* Line */
Imageline($im, $x, $y - 5, $x, $height - 15, $bar);
/* Draw data */
while (list ($key,$item) = each ($items)) {
    if ($item) {
        $pos = strpos($item, "^");
        $item_title = substr($item, 0, $pos);
        $value = substr($item, $pos + 1, strlen($item));
        /* Display percent 
        ImageString(
            $im, 3, $x - 25, $y - 2,
            round(($value / $total) * 100). "%",
            $labels
        );
	*/
        ImageString($im, 3 ,$x - $label_width, $y - 2, $item_title, $labels);
        /* Value right side rectangle */
        $px = $x + (round(($value / $total) * 100) * $unit);
        /* Draw rectangle value */
        ImageFilledRectangle($im, $x, $y - 2, $px, $y + $bar_width, $bar);
        /* Draw item title */
        #ImageString($im, 2 ,$x + 5, $y + 9, $item_title, $text);
        /* Draw empty rectangle */
        /*
		ImageRectangle(
            $im, $px + 1, $y - 2,
            ($x + (100 * $unit)), $y + $bar_width,
            $bar
		);
		*/
        /* Display numbers */
	if ($percent==true)
	{
        ImageString(
            $im, 3,
	    #max($x+2, $px + (imagefontwidth(3) * strlen($value . "%"))) ,
	    $px + 2,
	    $y - 2,
            $value . "%",
            $text
        );
	}
	else
	{
	    if ($mode == "aggregate") 
	    {
		$value .= " (" . round(($value/$total)*100, 0) . "%)";
	    }
        ImageString(
            $im, 3,
	    #max($x+2, $px + (imagefontwidth(3) * strlen($value))) , 
	    $px + 2,
	    $y - 2,
            $value, 
            $text
        );
	}
    }
    $y = $y + ($bar_width + 20);
}
/* Display image */
ImagePng($im);
/* Release allocated resources */
ImageDestroy($im);
?>
