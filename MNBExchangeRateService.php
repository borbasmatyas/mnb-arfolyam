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
     * A legutolsó napi jegyzés árfolyamtáblázatát adja vissza. Az adatok között csak az adott napon jegyzett devizák szerepelnek.
     * @param string $currencyCode Egy konkrét, vagy az összes deviza (ha nincs megadva)
     * @return array Az  árfolyamokok tömbje
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
            $this->checkCurrencyFormat($currencyCode);

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
     * @return array Az árfolyamok tömbje
     * 
     */
    public function getExchangeRates($startDate, $endDate, $currencyCode) {
  
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
     * checkCurrencyFormat
     * Ellenőrzi a devizakód formátumát, de annak meglétét nem.
     * 
     * @param string $currencyCode A devizakód, vagy vesszővel elválasztott devizakódok
     * @return void
     * 
     */
    private function checkCurrencyFormat($currencyCode = false) {

        // Bemenet ellenőrzése
        if (!$currencyCode) {
            throw new Exception("Nem adtál meg devizakódot.");
        }

        if (!preg_match('/^([A-Z]{3}(, ?[A-Z]{3})*)$/', $currencyCode)) {
            throw new Exception("Érvénytelen devizakód: $currencyCode.");
        }

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

