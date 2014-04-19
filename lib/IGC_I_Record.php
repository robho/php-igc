<?php
/**
* IGC_I_Record class extends IGC_Record. The I record defines the extension 
* of the mandatory fix B Record. Only one I record is allowed in each file.
*
* @see IGC_Record
*
* @version 0.1
* @author Mike Milano <coder1@gmail.com>
* @project php-igc
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
?>