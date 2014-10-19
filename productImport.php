<?php

require( __DIR__ . '/vendor/autoload.php' );
require( __DIR__ . '/product.php' );
use Respect\Validation\Validator as Validator;
use KzykHys\CsvParser\CsvParser as CsvParser;

$args = CommandLine::parseArgs( $_SERVER['argv'] );

if( !isset( $args[0] ) || !isset( $args[1] ) )
{
    print 'need csv and report files as commandline arguments in that order';
    exit();
}
//If test arg is set get connection to database exit on failure
$insertQuery = NULL;
if( isset( $args['test'] ) )
{ 
    try
    {
        $host = isset( $args['host'] ) ? $args['host'] : 'localhost';
        $name = NULL;
        if( !isset( $args['name'] ) )
        {
            throw new Exception( 'Databae name required' );
        }
        $name = $args['name'];
        $user = isset( $args['root'] ) ? $args['root'] : 'root';
        $pass = isset( $args['pass'] ) ? $args['pass'] : '';
        $conn = new PDO( 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8', $user, $pass );
        $insertQuery = $conn->prepare(
                'INSERT INTO `tblproductdata` ( ' .
                '`strProductCode`, ' .
                '`strProductName`, ' .
                '`strProductDesc`, ' .
                '`intProductStock`, ' .
                '`decProductCost`, ' .
                '`dtmAdded`,' .
                '`dtmDiscontinued` ) ' .
                'VALUES ( :code, :name, :description, :stock, :cost, :added, :discontinued )' );
           
    }
    catch( Exception $ex )
    {
        print $ex->getMessage();
        exit();
    }
}

//Open csv file using comandline arg
$csv = NULL;
$report = NULL;
try
{
    $csv = CsvParser::fromFile( $args[0], array( 'offset' => 1, 'encoding' => 'utf-8' ) );
    $report = fopen( $args[1], 'w' );
}
 catch ( Exception $ex )
 {
     print $ex->getMessage();
     exit();
 }

//read in csv file skipping first line;
$lines = $csv->getIterator();
$lines->rewind();
$validProducts = array();
$lineNumber = 2;
while( $lines->valid() )
{
    $line = $lines->current();
    //create product object
    $product = new Product($line[0], $line[1], $line[2], $line[3], $line[4], $line[5] );
    
    //validate product
    if( $product->validateProduct( $validProducts ) )
    {
        array_push( $validProducts, $product->getCode() );
    }
    
    //check insertion rules
    $product->toBeInserted( 5, 1000, 10 );
    
    //if it's not valid write to report else if we have an insertion query insert product
    if( !$product->isValid() )
    {
        fwrite( $report, 'Product at line ' . $lineNumber . " will not be inserted \n" );
        fwrite( $report, $product->getMessages() );
    }
    else if( $insertQuery )
    {
        $insertQuery->execute( $product->toArray() );
    }
    
    $lines->next();
    $lineNumber++;
}



