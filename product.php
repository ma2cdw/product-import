<?php
/**
 * Description of product
 * Class for validating and processing a product imported from a csv file *
 * @author christopher.williams
 */

use Respect\Validation\Validator as Validator;

class Product {
    private $code;
    private $name;
    private $description;
    private $stock;
    private $cost;
    private $discontinuedDate;
    private $added;
    private $isValid;
    private $formatedMessages;
    private $importErrors;
    
    /**
     * Constructs product, sets descontinued date based on pressence of discontinued value
     * @param string $code
     * @param string $name
     * @param string $description
     * @param mixed $stock
     * @param mixed $cost
     * @param mixed $discontinued
     */
    public function __construct( $code, $name, $description, $stock, $cost, $discontinued = NULL )
    {
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->stock = (int) $stock;
        $this->cost = (int) $cost;
        $this->added = new DateTime();
        if( $discontinued ) $this->discontinuedDate = new DateTime();
        $this->isValid = false;
        $this->formatedMessages = '';
        $this->importErrors = array();
    }
    
    /**
     * Returns products code
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * Returns formated error messages
     * @return string
     */
    public function getMessages()
    {
        array_walk_recursive( $this->importErrors, 'Product::formatMesssages' );
        return $this->formatedMessages;
    }
    
    /**
     * Returns vaild flag of product
     * @return bool
     */
    public function isValid()
    {
        return $this->isValid;
    }
    
    /**
     * Validates product based on what has already been flaged for insertion
     * @param array $previousEntries
     * @return bool
     */
    public function validateProduct( $previousEntries )
    {
        $this->validateCode( $previousEntries );
        $this->validateCost();
        $this->validateStock();
        $this->isValid =  empty( $this->importErrors );
        return $this->isValid;
    }
    
    /**
     * Flags product based on the cost and amount of stock for insertion
     * @param float $minCost
     * @param float $maxCost
     * @param int $stock
     * @return bool
     */
    public function toBeInserted( $minCost, $maxCost, $stock )
    {
        if( $this->isValid )
        {
            if( $this->cost < $minCost && $this->stock < $stock )
            {
                array_push( $this->importErrors, 'Product has less than ' . $stock . ' stock and costs less than £' . $minCost );
            }
            else if( $this->cost > $maxCost )
            {
                array_push( $this->importErrors, 'Product costs more than £' . $maxCost );
            }
            $this->isValid = empty( $this->importErrors );
        }
        return $this->isValid;
    }
    
    /**
     * Validates that a product with the same code is not already in a list of products to be inserted
     * @param array $previousEntries
     */
    private function validateCode( $previousEntries )
    {
        try
        {
            Validator::not( Validator::in( $previousEntries ) )->assert( $this->code );
        }
        catch( Exception $ex )
        {
            array_push( $this->importErrors, $ex->findMessages( array( 'in' => '`{{input}}` is not a unique product code' ) ) );
        }
    }
    
    /**
     * Validates the stock value of the product
     */
    private function validateStock()
    {
        try
        {
            Validator::int()->noWhitespace()->assert( $this->stock );
        }
        catch( Exception $ex )
        {
            array_push( $this->importErrors, $ex->findMessages( array( 'int' => 'Stock value must be an integer, `{{input}}` given',
                                                                       'notEmpty' => 'A stock value must be given' ) ) );
        } 
    }
    
    /**
     * Validates the cost value of the product
     */
    private function validateCost()
    {
        try
        {
            Validator::regex( '/^(0|([1-9]\d*))(\.\d{1,2})?$/' )->noWhitespace()->assert( $this->cost );
        }
        catch( Exception $ex )
        {
            array_push( $this->importErrors, $ex->findMessages( array( 'regex' => 'Cost must be a number to two decimal places, `{{input}}` given',
                                                                       'notEmpty' => 'A cost value must be given' ) ) );
        }
    }
    
    
    /**
     * Cereates formeted message string for import error report
     * @param string $message
     */
    private function formatMesssages( $message )
    {
        if( $message )
        {
            $this->formatedMessages .= "\t" . $message . "\n";
        }
    }
    
    /**
     * Return object as SQL formated array
     * @return array
     */
    public function toArray()
    {
        return array( 'code' => $this->code,
                      'name' => $this->name,
                      'description' => $this->description,
                      'stock' => $this->stock,
                      'cost' => $this->cost,
                      'added' => $this->added->format( 'Y-m-d H:i:s' ),
                      'discontinued' => $this->added->format( 'Y-m-d H:i:s' ) );
    }
}
