<?php
/**
* PHP_IGC version 0.1
*
* PHP_IGC Classes read and manage IGC files and data.
* Copyright (C) 2007  name of Mike Milano

* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.

* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

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

/**
* IGC_Record
* 
* The base record class to be extended by all other record classes.
*/
class IGC_Record
{
	/**
	* The single byte record type.
	* @access public
	* @var string
	*/
	public $type;
	/**
	* The raw record string from the IGC file
	* @access public
	* @var string
	*/
	public $raw;
}

/**
* IGC_A_Record class extends IGC_Record. The A Record is the first record in an IGC Data File.
* @see IGC_Record
*/
class IGC_A_Record extends IGC_Record
{
	/**
	* The single byte manufacturer code. Use PHP_IGC::GetManufacturerFromCode() for full string.
	* @access public
	* @var string
	*/
	public $manufacturer;
	/**
	* The 5 byte equipment ID
	* @access public
	* @var string
	*/
	public $unique_id;
	/**
	* ID Extension. (???)
	* @access public
	* @var string
	*/
	public $id_extension;
	
	/**
	* Class constructor creates the A record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'A';
		$this->raw = $record;
		
		$this->manufacturer = substr($record,1,1);
		$this->unique_id = substr($record,2,5);
		$this->id_extension = substr($record,6);
	}
}

/**
* IGC_G_Record class extends IGC_Record. The G Record verifies that the ASCII data has not been altered during or following the flight.
* @see IGC_Record
*/
class IGC_G_Record extends IGC_Record
{
	/**
	* The security code is a <= 75 byte string generated by the FDR. The manufacturer provides 
	* methods to check the integrity of the file within the security code.
	*
	* @access public
	* @var string
	*/
	public $security_code;
	
	/**
	* Class constructor creates the G record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'G';
		$this->raw = $record;
		
		$this->security_code = substr($record,1);
	}
}
	
/**
* IGC_H_Record class extends IGC_Record. The H records contain header information.
* @see IGC_Record
*/
class IGC_H_Record extends IGC_Record
{
	/**
	* Source
	*
	* @access public
	* @var string
	*/
	public $source;
	/**
	* Mnemonic
	*
	* @access public
	* @var string
	*/
	public $mnemonic;
	/**
	* Key
	*
	* @access public
	* @var string
	*/
	public $key;
	/**
	* Value
	*
	* @access public
	* @var string
	*/
	public $value;
	
	/**
	* Class constructor creates the H record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'H';
		$this->raw = $record;

		$this->source = substr($record,1,1);
		$this->mnemonic = substr($record,2,3);
		
		// explode the rest of the string on the colon
		$array = explode(":",substr($record,4));
		$this->key = $array[0];
		$this->value = $array[1];
	}
}

/**
* IGC_I_Record class extends IGC_Record. The I record defines the extension 
* of the mandatory fix B Record. Only one I record is allowed in each file.
* @see IGC_Record
*/
class IGC_I_Record extends IGC_Record
{
	/**
	* Start byte number
	*
	* @access public
	* @var integer
	*/
	public $start_byte_number;
	/**
	* Finish byte number
	*
	* @access public
	* @var integer
	*/
	public $finish_byte_number;
	/**
	* Mnemonic
	*
	* @access public
	* @var string
	*/
	public $mnemonic;
	
	/**
	* Class constructor creates the I record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'I';
		$this->raw = $record;
		
		$this->start_byte_number = substr($record,1,2);
		$this->finish_byte_number = substr($record,3,2);
		$this->mnemonic = substr($record,5,3);
	}
}

/**
* IGC_J_Record class extends IGC_Record. The J record  defines the extension K Record.
* @see IGC_Record
*/
class IGC_J_Record extends IGC_Record
{
	/**
	* Start byte number
	*
	* @access public
	* @var integer
	*/
	public $start_byte_number;
	/**
	* Finish byte number
	*
	* @access public
	* @var integer
	*/
	public $finish_byte_number;
	/**
	* Mnemonic
	*
	* @access public
	* @var string
	*/
	public $mnemonic;
	
	/**
	* Class constructor creates the J record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'J';
		$this->raw = $string;
		
		$this->start_byte_number = substr($record,1,2);
		$this->finish_byte_number = substr($record,3,2);
		$this->mnemonic = substr($record,5,3);
	}
}

/**
* IGC_B_Record class extends IGC_Record. The mandatory data is: UTC, latitude, longitude, fix validity 
* and pressure altitude. It is recommended to include GPS altitude and fix accuracy if they are available.
* The B Record is a multiple instance record.
* @see IGC_Record
*/
class IGC_B_Record extends IGC_Record
{
	/**
	* Time array [0] hours, [1] minutes, [3] seconds
	*
	* @access public
	* @var array
	*/
	public $time_array;
	/**
	* Latitude array [0] degrees, [1] minutes, [2] decimal minutes, [3] direction
	*
	* @access public
	* @var array
	*/
	public $latitude;
	/**
	* Longitude array [0] degrees, [1] minutes, [2] decimal minutes, [3] direction
	*
	* @access public
	* @var array
	*/
	public $longitude;
	/**
	* Fixed valid. A: valid, V:nav warning
	*
	* @access public
	* @var string
	*/
	public $fixed_valid;
	/**
	* Pressure Altitude
	*
	* @access public
	* @var integer
	*/
	public $pressure_altitude;
	/**
	* GPS Altitude
	*
	* @access public
	* @var integer
	*/
	public $gps_altitude;
	/**
	* Fix Accuracy
	*
	* @access public
	* @var integer
	*/
	public $fix_accuracy;
	
	/**
	* Class constructor creates the B record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'B';
		$this->raw = $string;
		
		$this->time_array['h'] = substr($record,1,2);
		$this->time_array['m'] = substr($record,3,2);
		$this->time_array['s'] = substr($record,5,2);
		
		// set degrees, minutes, and decimal minutes
		// latitude
		$this->latitude = array();
		$this->latitude['degrees'] = substr($record,7,2);
		$this->latitude['minutes'] = substr($record,9,2);
		$this->latitude['decimal_minutes'] = substr($record,11,3);
		$this->latitude['direction'] = substr($record,14,1);
		
		$pm = $this->latitude['direction']=="S"?"-":"";
		$dd = (($this->latitude['minutes'].".".$this->latitude['decimal_minutes'])/60)+$this->latitude['degrees'];
		$this->latitude['decimal_degrees'] = $pm.$dd;
		
		// longitude
		$this->longitude = array();
		$this->longitude['degrees'] = substr($record,15,3);
		$this->longitude['minutes'] = substr($record,18,2);
		$this->longitude['decimal_minutes'] = substr($record,20,3);
		$this->longitude['direction'] = substr($record,23,1);
		
		$pm = $this->longitude['direction']=="W"?"-":"";
		$dd = (($this->longitude['minutes'].".".$this->longitude['decimal_minutes'])/60)+$this->longitude['degrees'];
		$this->longitude['decimal_degrees'] = $pm.$dd;
		
		// extended data
		if (strlen($record)>25) {
		
			// set fixed valid
			$this->fixed_valid = substr($record,25,1);
			
			// set pressure altitude
			$this->pressure_altitude = substr($record,26,5);
			
			// set gps altitude
			$this->gps_altitude = substr($record,31,5);
			
			// set fix accuracy
			$this->fix_accuracy = substr($record,36,3);
		}
	}
}

/**
* IGC_C_Record class extends IGC_Record. The C record is task data
* @see IGC_Record
*/
class IGC_C_Record extends IGC_Record
{
	/**
	* Record Time
	*
	* @access public
	* @var date
	*/
	public $time;
	/**
	* Flight Date
	*
	* @access public
	* @var date
	*/
	public $flight_date;
	/**
	* Task ID
	*
	* @access public
	* @var integer
	*/
	public $task_id;
	/**
	* Number of turning points in the task
	*
	* @access public
	* @var integer
	*/
	public $number_of_tps;
	/**
	* Latitude array [0] degrees, [1] minutes, [2] decimal minutes, [3] direction
	*
	* @access public
	* @var array
	*/
	public $latitude;
	/**
	* Longitude array [0] degrees, [1] minutes, [2] decimal minutes, [3] direction
	*
	* @access public
	* @var array
	*/
	public $longitude;
	/**
	* Comment
	*
	* @access public
	* @var string
	*/
	public $comment;
	
	/**
	* Class constructor creates the C record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'C';
		$this->raw = $record;
		
		// turning point
		if (preg_match('/^[0-9]{7}[N|S]{1}[0-9]{8}[E|W]{1}/',substr($record,1))) {
			
			// set degrees, minutes, and decimal minutes
			// latitude
			$this->latitude = array();
			$this->latitude['degrees'] = substr($record,1,2);
			$this->latitude['minutes'] = substr($record,3,2);
			$this->latitude['decimal_minutes'] = substr($record,5,3);
			$this->latitude['direction'] = substr($record,8,1);
			
			// longitude
			$this->longitude = array();
			$this->longitude['degrees'] = substr($record,9,3);
			$this->longitude['minutes'] = substr($record,12,2);
			$this->longitude['decimal_minutes'] = substr($record,14,3);
			$this->longitude['direction'] = substr($record,17,1);
			
			$this->comment = substr($record,18);
		}
		// new task
		else {
			$d = substr($record,1,2);
			$mo = substr($record,3,2);
			$y = substr($record,5,2);
			$h = substr($record,7,2);
			$m = substr($record,9,2);
			$s = substr($record,11,2);
			
			$this->time = strtotime($mo."/".$d."/".$y." ".$h.":".$m.":".$s);
			
			$d = substr($record,13,2);
			$mo = substr($record,15,2);
			$y = substr($record,17,2);
			
			$this->flight_date = strtotime($mo."/".$d."/".$y);
			
			$this->task_id = substr($record,19,4);
			$this->number_of_tps = substr($record,23,2);
			$this->comment = substr($record,25);
		}
	}
}

/**
* IGC_D_Record class extends IGC_Record. Differential GPS
* @see IGC_Record
*/
class IGC_D_Record extends IGC_Record
{
	
	/**
	* GPS Qualifier 1: GPS, 2:DGPS
	*
	* @access public
	* @var integer
	*/
	public $gps_qualifier;
	/**
	* Station ID
	*
	* @access public
	* @var string
	*/
	public $station_id;
	
	/**
	* Class constructor creates the D record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'D';
		$this->raw = $string;
		
		$this->gps_qualifier = substr($record,1,1);
		$this->station_id = substr($record,2,4);
	}
}

/**
* IGC_E_Record class extends IGC_Record. The E Record (Event) must immediately 
* precede a B Record which logs where the event occurred.
* 
* @see IGC_Record
*/
class IGC_E_Record extends IGC_Record
{
	/**
	* Time array [0] hours, [1] minutes, [3] seconds
	*
	* @access public
	* @var array
	*/
	public $time_array;
	/**
	* Mnemonic
	*
	* @access public
	* @var string
	*/
	public $mnemonics;
	/**
	* Comment
	*
	* @access public
	* @var string
	*/
	public $comment;
	
	/**
	* Class constructor creates the E record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($record)
	{
		$this->type = 'E';
		$this->raw = $string;
		
		$this->time_array['h'] = substr($record,1,2);
		$this->time_array['m'] = substr($record,3,2);
		$this->time_array['s'] = substr($record,5,2);
		
		$this->mnemonics = substr($record,7,3);
		$this->comment = substr($record,10);
	}
}

/**
* IGC_F_Record class extends IGC_Record. Satellite constelation.
* 
* @see IGC_Record
*/
class IGC_F_Record extends IGC_Record
{
	/**
	* Time array [0] hours, [1] minutes, [3] seconds
	*
	* @access public
	* @var array
	*/
	public $time_array;
	/**
	* Satellite ID
	*
	* @access public
	* @var string
	*/
	public $satellite_id;
	/**
	* Comments
	*
	* @access public
	* @var string
	*/
	public $comment;
	
	/**
	* Class constructor creates the F record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __consruct($record)
	{
		$this->type = 'F';
		$this->raw = $string;
		
		$this->time_array['h'] = substr($record,1,2);
		$this->time_array['m'] = substr($record,3,2);
		$this->time_array['s'] = substr($record,5,2);
		
	}
}

/**
* IGC_K_Record class extends IGC_Record. Extension Data. The information in the K Record is specified by the J Record.
* 
* @see IGC_Record
*/
class IGC_K_Record extends IGC_Record
{
	/**
	* Time array [0] hours, [1] minutes, [3] seconds
	*
	* @access public
	* @var array
	*/
	public $time_array;
	/**
	* Total Energy Altitude
	*
	* @access public
	* @var integer
	*/
	public $total_energy_altitude;
	
	/**
	* Class constructor creates the K record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __consruct($record)
	{
		$this->type = 'F';
		$this->raw = $record;
		
		$this->time_array['h'] = substr($record,1,2);
		$this->time_array['m'] = substr($record,3,2);
		$this->time_array['s'] = substr($record,5,2);
		
		$this->total_energy_altitude = substr($record,7,4);
	}
}

/**
* IGC_L_Record class extends IGC_Record. Log Book
* 
* @see IGC_Record
*/
class IGC_L_Record extends IGC_Record
{
	/**
	* Manufacturer
	*
	* @access public
	* @var string
	*/
	public $manufacturer;
	/**
	* Comment
	*
	* @access public
	* @var string
	*/
	public $comment;
	
	/**
	* Class constructor creates the L record from the raw IGC string
	*
	* $param	string	$record
	*/
	public function __construct($string)
	{
		$this->type = 'L';
		$this->raw = $string;
		
		$this->manufacturer = substr($string,1,1);
		$this->comment = substr($string,2);
	}
}
?>