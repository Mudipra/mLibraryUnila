<?php
# Jika tidak ada parameter GET yang dikirimkan, tidak ada respon yang diberikan
if ($_GET) {
    # Jika input type tidak ada, diberi nilai NULL
    $keywords = isset($_GET['keywords']) ? $_GET['keywords'] : NULL;

    ## BAGIAN 1 MENGOLAH HTML MENJADI OBYEK PHP ##
    # Mengambil HTML
    $url = "http://opac.unila.ac.id/index.php?search=search&keywords=" . rawurlencode($keywords);
    //    $url = "http://localhost/git/mLibraryUnila/dispatcher/OPACjamur.html";

    function curl_get_contents($targeturl)
    {

        $timeout = 5;
        // $cookie = tempnam("/tmp", "CURLCOOKIE");
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
        curl_setopt($ch, CURLOPT_URL, $targeturl);
        // curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    # Query and get back a page of results
    # file_get_contents() require php.ini to have line: allow_url_fopen = On
    // $html = file_get_contents($url);
    $html = curl_get_contents($url);

    # Create a DOM parser object
    $dom = new DOMDocument();

    # Parse the HTML.
    # The @ before the method call suppresses any warnings that
    # loadHTML might throw because of invalid HTML in the page.
    @$dom->loadHTML($html);
    # Mencetak HTML
    // print_r($dom->saveHTML());

    # Create DOMXPath Object and load DOMDocument Object into XPath for magical goodness
    $xpath = new DOMXPath($dom);

    # returns list
    # Mencari nilai berdasarkan persyaratan kondisi query
    # Informasi jumlah hasil pencarian
    $infoJumlahLengkap = $xpath->query("//div[@class='search-found-info']");
    $infoJumlah = $infoJumlahLengkap;
    $infoJumlahValue = $infoJumlah->item(0)->nodeValue;
    // echo $infoJumlahValue;

    # Himpunan/kumpulan daftar pustaka yang ditemukan berdasarkan <div class="item">
    $nlist = $xpath->query("//div[@class='item']");

    # Mendapatkan link/tautan halaman berikutnya berdasarkan <a class="next_link">
    $nextLink = $xpath->query("//a[@class='next_link']");

    # Jika satu halaman
    $nLink = NULL;
    # Jika lebih dari satu halaman, maka dibuat link halaman berikut
    # ditambahkan absolute URL karena tautan yang tersimpan masih menggunakan relative URL
    if ($nextLink->item(0) != NULL)
        $nLink = 'http://opac.unila.ac.id' . $nextLink->item(0)->attributes->item(0)->nodeValue;

    ## AKHIR BAGIAN 1 MENGOLAH HTML MENJADI OBYEK PHP ##

    ## BAGIAN 2 MEMBUAT RESPON KELUARAN JSON DARI OBYEK PHP ##
    /* JSON */
    # Obyek $respon menampung seluruh nilai yang akan diekspor ke JSON
    $respon =  new stdClass();

    # Memberikan label, Sebagai penanda bahwa ini adalah data dari opac.unila.ac.id
    $respon->label = "opac.unila.ac.id";

    # Memasukkan informasi jumlah hasil pencarian ke dalam obyek $respon
    $respon->info = $infoJumlahValue;

    # Penampung nilai yang akan dimasukkan ke dalam obyek $respon
    $no = 0; // sebagai nomor urut
    $judul = ''; // judul pustaka
    $pengarang = ''; // pengarang pustaka
    $callNumber = ''; // nomor panggil pustaka
    $tersedia = ''; // ketersediaan pustaka
    $cantuman = ''; // tautan menuju rincian pustaka

    # Menjadikan kumpulan elemen pada $nslist menjadi sebuah obyek $item (yang mewakili satu buah daftar pustaka)
    foreach ($nlist as $item) {
        # Menjadikan setiap elemen dom di dalam obyek $item sebagai $ite
        foreach ($item->childNodes as $ite) {
            # Menjadikan setiap elemen dom di dalam obyek $ite sebagai $it
            foreach ($ite->childNodes as $it) {
                # jika ada $it yang memiliki tag html h4, dimasukkan sebagai nilai $judul
                if ($it->nodeName == 'h4') {
                    $judul = $it->nodeValue;
                    continue;
                }
                # jika ada tag pada $it yang memiliki atribut, diperiksa disini
                # Agar tidak muncul warning https://stackoverflow.com/questions/2385834/php-dom-get-all-attributes-of-a-node
                if ($it->hasAttributes()) {
                    foreach ($it->attributes as $i) {
                        # jika ada yang memiliki atribut 'author', dimasukkan sebagai nilai $pengarang
                        if ($i->nodeValue == 'author') {
                            $pengarang = substr($it->nodeValue, 12);
                        }
                        # jika ada yang memiliki atribut 'customField callNumberField', dimasukkan sebagai nilai $callNumber
                        else if ($i->nodeValue == 'customField callNumberField') {
                            $callNumber = substr($it->nodeValue, 14, 3);
                        }
                        # jika ada yang memiliki atribut 'customField availabilityField', dimasukkan sebagai nilai $tersedia
                        else if ($i->nodeValue == 'customField availabilityField') {
                            $str = $it->nodeValue;
                            $tersedia = (int) filter_var($str, FILTER_SANITIZE_NUMBER_INT);
                        }
                        # jika ada yang memiliki atribut 'subItem', dimasukkan sebagai nilai $cantuman
                        # ditambahkan absolute URL karena tautan yang tersimpan masih menggunakan relative URL
                        else if ($i->nodeValue == 'subItem') {
                            $cantuman = 'http://opac.unila.ac.id' . $it->childNodes->item(0)->attributes->item(0)->nodeValue;
                        }
                    }
                }
            }
        }

        # Menambahkan data yang dikumpulkan dari $item dan menyusunnya sesuai keperluan ke dalam key data
        # $no digunakan sebagai indeks dari data
        $respon->data[$no] = array($judul, $pengarang, $callNumber, $tersedia, $cantuman); //ambil yang perlu saja

        # setelah data satuan ditambahkan ke key data, indeks ditambahkan satu
        $no += 1;
    }

    # Menambahkan informasi link/tautan ke halaman berikut
    $respon->nextLink = $nLink;

    echo json_encode($respon);
    /* JSON */
    ## AKHIR BAGIAN 2 MEMBUAT RESPON KELUARAN JSON DARI OBYEK PHP ##
}
