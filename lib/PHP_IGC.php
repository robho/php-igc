<?php
/**
* PHP_IGC
*
* This class is instanciated with the file path of the IGC file. It
* will create an array of IGC record objects for convenient use of the data.
*
* @version 0.1
* @author Mike Milano <coder1@gmail.com>
* @project php-igc
*/
class PHP_IGC
{
	/**
	* The date and time of the flight
	* @access public
	* @var string
	*/
	public $datetime;
	/**
	* The Pilot's name
	* @access public
	* @var string
	*/
	public $pilot;
	/**
	* The Glider type
	* @access public
	* @var string
	*/
	public $glider_type;
	/**
	* The Glider ID
	* @access public
	* @var string
	*/
	public $glider_id;
	/**
	* The max altitude of the flight
	* @access public
	* @var string
	*/
	public $max_altitude;
	/**
	* The minimum altitude of the flight
	* @access public
	* @var string
	*/
	public $min_altitude;
	/**
	* The total distance of the flight
	* @access public
	* @var string
	*/
	public $distance;
	
	/**
	* Class constructor creates the PHP_IGC object from a file path.
	*
	* @param 	string 	$file_path usually this will be the request vars
	* @return	bool 	Returns false if file doesn't exist.
	*/ 
	public function __construct($file_path)
	{
		if (!file_exists($file_path)) {
			return false;
		}
		
		$handle = @fopen($file_path, "r");
		if ($handle) {
			while (!feof($handle)) {
				$this->records[] = $this->getRecord(fgets($handle, 4096));
			}
		}
		
		return true;
	}
	
	/**
	* Returns an IGC record object
	*
	* @param 	string 		$string is the raw record line from an IGC file
	* @return	IGC_Record 	Returns the specific IGC_Record object or false if the record isn't supported.
	*/ 
	public function getRecord($string) {
		
		$classname = 'IGC_'.strtoupper(substr($string,0,1)).'_Record';
		if (class_exists($classname)) {
			return new $classname($string);
		} else {
			return false;
		}
	}
	
	/**
	* Sets the details of the IGC files from the record objects within
	*/ 
	public function setDetails()
	{
		$this->max_altitude = 0;
		$this->min_altitude = 80000;
		
		// set lowest and highest altitude
		if (is_array($this->records)) {
			foreach ($this->records as $each) {
				if ($each['type'] == 'B') {
					if ($each['pressure_altitude']>$this->max_altitude) {
						$this->max_altitude = $each['pressure_altitude'];
					} elseif ($each['pressure_altitude'] < $this->min_altitude) {
						$this->min_altitude = $each['pressure_altitude'];
					}
				}
			}
		}
		
		// reset to 0 if a minimum altitude was never recorded
		if ($this->min_altitude = 80000) {
			$this->min_altitude = 0;
		}
	}
		
	/**
	* Returns the HTML and Javascript to draw the path over GoogleMaps
	*
	* @param 	string	$key is the GoogleAPI developer key
	* @param	integer	$width in pixels
	* @param	integer	$height in pixels
	* @return	string 	Returns HTML, CSS, and JavaScript
	*/ 
	public function getMap($key, $width, $height)
	{
		if (count($this->records)<1) {
			$code = "invalid file";
			return $code;
		}

		$code = '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$key.'" type="text/javascript"></script>
<br /><br />
<div id="map" style="width: '.$width.'px; height: '.$height.'px; border: 2px solid #111111;"></div>

<script type="text/javascript">
function loadIGC() {

	var map = new GMap2(document.getElementById("map"));
	map.addControl(new GSmallMapControl());
	map.addControl(new GMapTypeControl());
	';

	$started = false;
	foreach ($this->records as $each) {

		if ($each->type == "B") {
			
			if (!$started) {
				$code .= "map.setCenter(new GLatLng(".$each->latitude['decimal_degrees'].", ".$each->longitude['decimal_degrees']."), 13, G_SATELLITE_MAP);\n";
				$code .= "var polyline = new GPolyline([\n";
				$started = true;
			}
			
			$code .= "new GLatLng(".$each->latitude['decimal_degrees'].", ".$each->longitude['decimal_degrees']."),\n";
		}
	}
	
	$code .= '
	], "#FF0000", 2);
	map.addOverlay(polyline);
	
	}

    window.onload = loadIGC;
    </script>';
    
    		return $code;
	
	}
	
	/**
	* Returns the full manufacturer string from the code defined in the A record
	*
	* @param 	string	$code is the manufacturer's code from the A record
	* @return	string 	Full manufacturer string
	*/ 
	public static function GetManufacturerFromCode($code)
	{
		// manufacturer array
		$man = array();
		
		$man['B'] = "Borgelt";
		$man['C'] = "Cambridge";
		$man['E'] = "EW";
		$man['F'] = "Filser";
		$man['I'] = "Ilec";    
		$man['M'] = "Metron";
		$man['P'] = "Peschges";
		$man['S'] = "Sky Force";
		$man['T'] = "PathTracker";
		$man['V'] = "Varcom";
		$man['W'] = "Westerboer";
		$man['Z'] = "Zander";
		$man['1'] = "Collins";
		$man['2'] = "Honeywell";
		$man['3'] = "King";
		$man['4'] = "Garmin";
		$man['5'] = "Trimble";
		$man['6'] = "Motorola";
		$man['7'] = "Magellan";
		$man['8'] = "Rockwell";
		
		if (!$man[(string)$code]) {
			return false;
		}
		
		return $man[(string)$code];
	}
}
?>