# MNB árfolyam

A Magyar Nemzteti Bank (MNB) által nyújtott webszolgáltatás segítségével tudunk árfolyam adatokat lekérni, aktuálisat és multbélit egyaránt.

## Függőségek és telepítés

A használathoz telepíteni és engedélyezni kell a PHP-ban a SOAP kiterjesztést.

```bash
sudo apt-get install php-soap
```

A *php.ini* fájlban el kell távolítani a ; -t a következő sorból:
```php.ini
extension=soap
```

Végül újra kell indítani az Apache szervert (vagy amit használunk)

```bash
sudo systemctl restart apache2
```



## Példa a használatra

A használatához a `MNBExchangeRateService` osztályt kell példányosítani, ami alapértelmezetten az MNB által biztosított webszolgáltatás URL-t használja.

```php
$mnb = new MNBExchangeRateService();
```

Lekérhetjük az MNB által biztosított árfolyam szolgáltatás időintervallumát.

```php
    $dateInterval = $mnb->getDateInterval();
    echo "Az MNB árfolyam szolgáltatás időintervalluma: {$dateInterval['startdate']} - {$dateInterval['enddate']}\n";
```

Lekérhetüjük az akutális árfolyamokat.

```php
    $rates = $mnb->getCurremtExchangerates();
    echo "Aktuális árfolyamok:\n";
    foreach ($rates as $rate) {
        echo chr(9)."{$rate['curr']}: {$rate['rate']}\n";
    }
```

vagy csak egy konkrétat is

```php
try {
    $rate = $mnb->getCurremtExchangerates('EUR');
    echo "Aktuális EUR árfolyam: $rate\n";
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage() . "\n";
}
```


*(Itt tartok eddig)*


## Információk az MNB oldaláról

[Aktuális és a régebbi árfolyamok webszolgáltatásának
dokumentációja](https://www.mnb.hu/letoltes/aktualis-es-a-regebbi-arfolyamok-webszolgaltatasanak-dokumentacioja-1.pdf)

---

> **Tájékoztatás az árfolyam Webservice működéséről**
> Az MNB új webportáljának elindításával párhuzamosan felhasználói visszajelzések alapján azt tapasztaltuk, hogy az árfolyamok automatikus lekérdezése az ezt végző programok egy részénél nem működik. A jelenség megoldásán dolgozunk, annak érdekében, hogy a korábban használt összes programmal elérhető legyen a szolgáltatás. Addig is felhívjuk figyelmüket, hogy az alábbi javasolt megoldás használatával a Webservice továbbra is elérhető:
>
> A SOAP alapú szervizek fogyasztásához a PHP 5.0.1 óta az alapértelmezett eszközkészletben található SoapClient használata ajánlott.
>
> Például az elérhető pénznemek, illetve az aktuális alapkamat lekérdezése:
> [Soap service fogyasztása (php)](https://www.mnb.hu/letoltes/soap-service-fogyasztasa-php.pdf)
>
> Magyar Nemzeti Bank