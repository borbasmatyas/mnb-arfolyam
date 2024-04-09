<?php



class MNBExchangeRateService
{
	// Az MNB árfolyam szolgáltatás URL-je
    private $serviceUrl = 'http://www.mnb.hu/arfolyamok.asmx?wsdl';

	// SOAP kliens
	private $client;

	public function __construct() {
        try {
            $this->client = new SoapClient($this->serviceUrl, ['exceptions' => true]);
        } catch (SoapFault $e) {
            throw new Exception("A szolgáltatás nem érhető el: " . $e->getMessage());
        }
    }

    /**
     * getCurremtExchangerates
     * 
     * 
     */
    public function getCurremtExchangerates($currencyCode = false) {
        
        try {
            $response = $this->client->GetCurrentExchangeRates([]);
        } catch (SoapFault $e) {
            throw new Exception("SOAP Hiba: " . $e->getMessage());
        }

        $xml = simplexml_load_string($response->GetCurrentExchangeRatesResult);
        if (!$xml) {
            throw new Exception("Nem elérhető az árfolyam információ.");
        }

        if ($currencyCode) {
            // Ellenőrizzük a devizakód formátumát
            //$this->checkCurrencyFormat($currencyCode);

            foreach ($xml->Day->Rate as $rate) {
                if ($rate['curr'] == $currencyCode) {
                    return (string)$rate;
                }
            }

            throw new Exception("Nem elérhető az árfolyam ehhez: $currencyCode.");
        } else {
            $rates = [];
            foreach ($xml->Day->Rate as $rate) {
                $rates[] = ['curr' => (string)$rate['curr'], 'rate' => (string)$rate];
            }
            return $rates;
        }
    }

    /**
     * getExchangeRates
     * Az átadott paramétereknek megfelelő árfolyamtáblázatot adja vissza. 
     * A dátumokat év-hó-nap formában (kötőjellel elválasztva), 
     * a devizaneveket vesszővel elválasztva, a három nagybetűs rövidítésükkel kell megadni.
     * 
     * @param string $startDate A lekérdezés kezdő dátuma
     * @param string $endDate A lekérdezés záró dátuma
     * @param string $currencyCode A lekérdezett deviza, vagy devizák kódja vesszővel elválasztva
     * 
     * @return array Az árfolyam táblázat
     * 
     */
    public function getExchangeRates($startDate, $endDate, $currencyCode) {
        // Ellenőrizzük a dátum formátumot
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            throw new Exception("Érvénytelen dátum formátum. Kérlek, év-hó-nap formátumban add meg a dátumot.");
        }

        // Ellenőrizzük a devizakód formátumát
        //$this->checkCurrencyFormat($currencyCode);

        // Megkérdezzük az MNB-től az árfolyamokat
        try {
            $response = $this->client->GetExchangeRates([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'currencyCode' => $currencyCode
            ]);
        } catch (SoapFault $e) {
            throw new Exception("SOAP Hiba: " . $e->getMessage());
        }

        $xml = simplexml_load_string($response->GetExchangeRatesResult);
        if (!$xml) {
            throw new Exception("Nem elérhető az árfolyam információ.");
        }

        // Készítünk egy tömböt az árfolyamokkal
        $rates = [];
        foreach ($xml->Day->Rate as $rate) {
            $rates[] = [
                'date' => (string)$xml->Day['date'],
                'curr' => (string)$rate['curr'],
                'rate' => (string)$rate
            ];
        }

        if (empty($rates)) {
            throw new Exception("Nem elérhető az árfolyam a megadott időszakban és devizákra.");
        }

        return $rates;
    }

    /**
     * getDateInterval
     * 
     * Visszaadja az első és az utolsó napot melyhez tartozik árfolyamtáblázat.
     * 
     * @param none
     * @return array Az MNB árfolyam szolgáltatás időintervalluma
     */
    public function getDateInterval() {
        try {
            $response = $this->client->GetDateInterval([]);
        } catch (SoapFault $e) {
            throw new Exception("SOAP Hiba: " . $e->getMessage());
        }

        $xml = simplexml_load_string($response->GetDateIntervalResult);
        if (!$xml) {
            throw new Exception("Nem elérhető az időintervallum információ.");
        }

        // Készítünk egy tömböt az időintervallummal
        return [
            'startdate' => (string)$xml->DateInterval['startdate'],
            'enddate' => (string)$xml->DateInterval['enddate']
        ];
    }

    /**
     * getCurrencyUnits
     * Visszaadja a paraméterben megadott deviza(ák) egységét.
     * 
     * @param string $currencyCode A devizák neve vesszővel elválasztva
     * @return array A devizák egysége
     * 
     */
    public function getCurrencyUnits($currencyCode) {

        // Ellenőrizzük a devizakód formátumát
        ////$this->checkCurrencyFormat($currencyCode);

        // Megkérdezzük az MNB-től az egységeket
        try {
            $response = $this->client->GetCurrencyUnits([
                'currencyCode' => $currencyCode
            ]);
        } catch (SoapFault $e) {
            throw new Exception("SOAP Hiba: " . $e->getMessage());
        }

        $xml = simplexml_load_string($response->GetCurrencyUnitsResult);
        if (!$xml) {
            throw new Exception("Nem elérhető az egység információ.");
        }

        $units = [];
        foreach ($xml->Units->Unit as $unit) {
            $units[] = [
                'curr' => (string)$unit['curr'],
                'unit' => (string)$unit
            ];
        }

        if (empty($units)) {
            throw new Exception("Nem elérhető az egység a megadott devizákra.");
        }

        return $units;
    }

    /**
     * checkCurrencyFormat
     * Ellenőrzi a devizakód formátumát, de annak meglétét nem.
     * 
     * @param string $currencyCode A devizakód, vagy vesszővel elválasztott devizakódok
     * @return void
     * 
     */
    private function checkCurrencyFormat($currencyCode = false) {
        /*
        // Bemenet ellenőrzése
        if (!$currencyCode) {
            throw new Exception("Nem adtál meg devizakódot.");
        }

        if (!preg_match('/^([A-Z]{3}(, ?[A-Z]{3})*)$/', $currencyCode)) {
            throw new Exception("Érvénytelen devizakód: $currencyCode.");
        }
        */
        return true;
        
    }


  
}


$mnb = new MNBExchangeRateService();

header ('Content-Type: text/plain; charset=utf-8');

// teszt: getDateInterval
try {
    $dateInterval = $mnb->getDateInterval();
    echo "Az MNB árfolyam szolgáltatás időintervalluma: {$dateInterval['startdate']} - {$dateInterval['enddate']}\n";
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage() . "\n";
}


// teszt: getCurremtExchangerates
try {
    $rates = $mnb->getCurremtExchangerates();
    echo "Aktuális árfolyamok:\n";
    foreach ($rates as $rate) {
        echo chr(9)."{$rate['curr']}: {$rate['rate']}\n";
    }
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage() . "\n";
}


// teszt: getCurremtExchangerates
try {
    $rate = $mnb->getCurremtExchangerates('EUR');
    echo "Aktuális EUR árfolyam: $rate\n";
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage() . "\n";
}

// teszt: getExchangeRates
try {
    $rates = $mnb->getExchangeRates('2021-01-01', '2021-01-10', 'EUR');
    echo "Árfolyamok:\n";
    foreach ($rates as $rate) {
        echo chr(9)."{$rate['date']} {$rate['curr']}: {$rate['rate']}\n";
    }
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage() . "\n";
}

// teszt: getCurrencyUnits
try {
    $units = $mnb->getCurrencyUnits('EUR,USD');
    echo "Deviza egységek:\n";
    foreach ($units as $unit) {
        echo chr(9)."{$unit['curr']}: {$unit['unit']}\n";
    }
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage() . "\n";
}