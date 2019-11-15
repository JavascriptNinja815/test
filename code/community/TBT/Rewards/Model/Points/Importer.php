<?php
/**
 * An implementation of TBT_Rewards_Model_Importer to import points from a CSV file through the cron
 */
class TBT_Rewards_Model_Points_Importer extends TBT_Rewards_Model_Importer 
{	
    protected $_importerType = "rewards/points_importer";	
    protected $_emailSubject = "Your points have finished importing";
    protected $_csvHeaders = array();
    
    // locks the import - used to validate the file without actually importing anything
    protected $_isDryRun = false;

    /**
     * Makes sure import file exists and CSV headers are appropriate
     * 
     * @throw Exception if anything goes wrong
     * @return TBT_Rewards_Model_Points_Importer
     */
    public function validateFile()
    {
        $this->_isDryRun = true;
        $this->_doImport();
        return $this;
    }
    
    /**
     * Will generate a 1 line summary of the report.
     * @return string
     */
    public function getReportSummary()
    {
        if (!$this->_isImportFinished) {
            throw new Exception("Please run the importer first.");
        }

        return "<b>" . count($this->_successes) . " out of " . $this->_countItemsProcessed . "</b> point entries have been successfully imported.";
    }

    /**
     * @throws Exception if there's a problem with the file
     * @return TBT_Rewards_Model_Points_Importer
     */
    protected function _doImport() 
    {
        /* Extract options and paramaters from the importer base class */
        $filename = $this->getFile();

        /* Throw an exception if we've already ran an import on this object instance */
        if ($this->_isImportFinished) throw new Exception("An import has already been executed in this object instance.");

        /* Open file handle and read csv file line by line separating comma delaminated values */
        if (!file_exists($filename)) {
            throw new Exception("File doesn't exist: \"{$filename}\"");
        }

        /* Local Variables */
        $hasError = false;
        $errorMsg = "";
        $line     = 0;

        /* Store indices of titles on first line of csv file */
        $numberOfPointsColumnIndex = -1;
        $customerIdColumnIndex     = -1;
        $customerEmailColumnIndex  = -1;
        $websiteIdIndex            = -1;

        /* Open file handle and read csv file line by line separating comma delaminated values */
        $handle = fopen($filename, "r");

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($line == 0) {
                $this->_csvHeaders = $data;
                
                // This is the first line of the csv file. It usually contains titles of columns
                // Next iteration will propagate to "else" statement and increment to line 2 immediately
                $line = 1;

                /* Read in column headers and save indices if they appear */
                $num = count($data);
                for ($index = 0; $index < $num; $index++) {
                    $columnTitle = trim(strtolower($data [$index]));
                    if ($columnTitle === "customer_id") {
                        $customerIdColumnIndex = $index;
                    }
                    if ($columnTitle === "points_amount") {
                        $numberOfPointsColumnIndex = $index;
                    }
                    if ($columnTitle === "customer_email") {
                        $customerEmailColumnIndex = $index;
                    }
                    if ($columnTitle === "website_id") {
                        $websiteIdIndex = $index;
                    }
                }

                /* Terminate if no customer identifier column found */
                if ($customerEmailColumnIndex == -1 && $customerIdColumnIndex == -1) {
                    Mage::throwException(
                        Mage::helper('rewards')->__("Error on line") . " " . $line . ": " . Mage::helper('rewards')->__(
                            "No customer identifier in CSV file. Please check the contents of the file."
                        )
                    );
                }

                /* Terminate if no points column found */
                if ($numberOfPointsColumnIndex == -1) {
                    Mage::throwException(
                        Mage::helper('rewards')->__("Error on line") . " " . $line . ": " . Mage::helper('rewards')->__(
                            "No identifier for \"points_amount\" in CSV file. Please check the contents of the file."
                        )
                    );
                }
            } else {
                if ($this->_isDryRun) {
                    break;
                }
                
                if ($line === 1) {
                    $this->markStartOfImport();
                }
                
                try {
                    // This handles the rest of the lines of the csv file

                    if (!isset($data[$numberOfPointsColumnIndex])) {
                        // could be an empty line
                        continue;
                    }

                    $line++;
                    
                    /* Prepare line data based on values provided */
                    $num = count($data);
                    $num_points = $data [$numberOfPointsColumnIndex];
                    $custId = null;
                    $cusEmail = null;
                    $websiteId = null;

                    if ($websiteIdIndex != -1) {
                        $websiteId = array_key_exists($websiteIdIndex, $data) ? $data [$websiteIdIndex] : null;
                    }
                    if ($customerEmailColumnIndex != -1) {
                        // customer email.
                        $cusEmail = array_key_exists($customerEmailColumnIndex, $data)
                        ? $data [$customerEmailColumnIndex]
                        : null;
                    }
                    if ($customerIdColumnIndex != -1) {
                        // customer id.
                        $custId = array_key_exists($customerIdColumnIndex, $data)
                        ? $data [$customerIdColumnIndex]
                        : null;
                    } else {
                        // If no customer_id provided, try finding the id by their email
                        // Customer email is website dependent. Either load default website or look at website ID provided in file
                        if ($websiteId == null) {
                            $websiteId = Mage::app()->getDefaultStoreView()->getWebsiteId();
                        } else {
                            $websiteId = Mage::app()->getWebsite($websiteId)->getId();
                        }
                        $custId = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($cusEmail)
                            ->getId();
                        if (empty ($custId)) {
                            $hasError = true;
                            $errorMsg .= "- " . Mage::helper('rewards')->__(
                                    "Error on line"
                                ) . " " . $line . ": " . Mage::helper('rewards')->__(
                                    "Customer with email"
                                ) . " \"" . $cusEmail . "\" " . Mage::helper('rewards')->__(
                                    "was not found in website with id #"
                                ) . $websiteId . ".\n";
                            
                            $this->_reportError($errorMsg, $data, $line);
                            continue;
                        }
                    }
                    // Make sure customer_id provided is actually valid
                    if (Mage::getModel('customer/customer')->load($custId)->getId() == null) {
                        $hasError = true;
                        $errorMsg .= "- " . Mage::helper('rewards')->__(
                                "Error on line"
                            ) . " " . $line . ": " . Mage::helper('rewards')->__(
                                "Customer with id #"
                            ) . $custId . " " . Mage::helper('rewards')->__("was not found.") . "\n";
                        $this->_reportError($errorMsg, $data, $line);
                        continue;
                    }

                    /* Start Import */
                    //Load in transfer model
                    $transfer = Mage::getModel('rewards/transfer');
                    
                    //Load it up with information
                    $transfer->setId(null)
                        // number of points to transfer.  This number can be negative or positive, but not zero
                        ->setQuantity($num_points)
                        ->setCustomerId($custId)// the id of the customer that these points will be going out to
                        ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('adjustment'))
                        //This is optional
                        ->setComments(Mage::helper('rewards/config')->getDefaultMassTransferComment());

                    // Checks to make sure you can actually move the transfer into the new status
                    // STATUS_APPROVED would transfer the points in the approved status to the customer
                    if ($transfer->setStatusId(null, TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED)) {
                        $transfer->save(); //Save everything and execute the transfer
                    }
                    
                    $successMessage = Mage::helper('rewards')->__('where transfered to the customer with ID');
                    $successMessage = "$num_points $successMessage $custId";
                    $this->_reportSuccess($successMessage, $data, $line);

                } catch (Exception $e) {
                    // Any other errors which happen on each line should be saved and reported at the very end
                    Mage::logException($e);
                    $hasError = true;
                    $errorMsg .= "- " . Mage::helper('rewards')->__(
                            "Error on line"
                        ) . " " . $line . ": " . $e->getMessage() . "\n";
                    
                    $this->_reportError($e->getMessage(), $data, $line);
                }
                
                // Don't count the first line (titles)
                $this->_countItemsProcessed = $line - 1;					
                $this->setCountProcessed($this->_countItemsProcessed);
                $this->save();
            }
        }

        fclose($handle);
        if ($hasError) {
            // If there were any errors saved, now's the time to report them
            Mage::throwException(
                Mage::helper('rewards')->__("Points were imported with the following errors:") . "\n" . $errorMsg
            );
        }

        if ($line === 0) {
            throw new Exception("Empty file: \"{$filename}\"");	
        }

        if ($this->_printToStdOut) {
            Mage::helper('rewards/debug')->printMessage("Import complete!");
        }

        $this->markEndOfImport();
        return $this;
    }

    /**
     * Will generate a report of the import which was recently completed by this instance.
     * 
     * @throws Exception if the import has not been run yet.
     * @return string a report containing all the errors, warnings and successes in the import
     */
    public function getReport()
    {
        if (!$this->_isImportFinished) {
            throw new Exception("Please run the importer first.");
        }

        if (!empty($this->_report)) return $this->_report;

        $report = $this->getReportSummary();
        $report .= "Details are below:\n\n\n\n";

        if (count($this->_errors) > 0) {
            $report .= "<b><i>" . count($this->_errors) . " enteries</i></b> <b style=\"color: red;\">failed</b> because of errors:\n\n";
            $report .= "<table  border=\"1\" cellpadding=\"5\" cellspacing=\"0\" width=\"80%\" style=\"border: 1px solid black; border-collapse: collapse;\">";
            $report .= "	<tr>";
            $report .= "		<th style=\"text-align:left\">line_number</th>";
            foreach ($this->_csvHeaders as $header){
                $report .= "	<th style=\"text-align:left\">{$header}</th>";
            }
            $report .= "		<th style=\"text-align:left\">error_message</th>";
            $report .= "	</tr>";
            foreach ($this->_errors as $item) {
                $report .= "	<tr>";
                $report .= "		<td>{$item["lineNumber"]}</td>";
                foreach ($item["line"] as $param) {
                    $report .= "	<td>{$param}</td>";
                }
                $report .= "		<td>" . str_replace(",", ";", $item["message"]) . "</td>";
                $report .= "	</tr>";
            }
            $report .= "</table>";
            $report .= "\n\n\n";
        }


        if (count($this->_warnings) > 0) {
            $report .= "<b><i>" . count($this->_warnings) . " entries</i></b> produced <b style=\"color: orange;\">warnings</b>:\n\n";
            $report .= "<table  border=\"1\" cellpadding=\"5\" cellspacing=\"0\" width=\"80%\" style=\"border: 1px solid black; border-collapse: collapse;\">";
            $report .= "	<tr>";
            $report .= "		<th style=\"text-align:left\">line_number</th>";
            foreach ($this->_csvHeaders as $header){
                $report .= "	<th style=\"text-align:left\">{$header}</th>";
            }
            $report .= "		<th style=\"text-align:left\">warning_message</th>";
            $report .= "	</tr>";		
            foreach ($this->_warnings as $item) {
                $report .= "	<tr>";
                $report .= "		<td>{$item["lineNumber"]}</td>";
                foreach ($item["line"] as $param) {
                    $report .= "	<td>{$param}</td>";
                }
                $report .= "		<td>" . str_replace(",", ";", $item["message"]) . "</td>";
                $report .= "	</tr>";
            }
            $report .= "</table>";
            $report .= "\n\n\n";
        }


        $report .= "<b><i>" . count($this->_successes) . " enteries</i></b> were imported <b style=\"color: green;\">successfully</b>:\n\n";
        if (count($this->_successes) > 0){
            $report .= "<table  border=\"1\" cellpadding=\"5\" cellspacing=\"0\" width=\"80%\" style=\"border: 1px solid black; border-collapse: collapse;\">";
            $report .= "	<tr>";
            foreach ($this->_csvHeaders as $header){
                $report .= "	<th style=\"text-align:left\">{$header}</th>";
            }
            $report .= "	</tr>";
            foreach ($this->_successes as $item) {
                $report .= "	<tr>";
                foreach ($item["line"] as $param) {
                        $report .= "	<td>{$param}</td>";
                }
                $report .= "	</tr>";
            }
            $report .= "</table>";
        }

        $this->_report = nl2br($report);
        return $this->_report;
    }
}
