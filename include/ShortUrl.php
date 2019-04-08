<?php

class ShortUrl
{
    protected static $chars = "bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ123456789";
    protected static $table = "short_urls";
    protected static $checkUrlExists = true;

    protected $pdo;
    protected $timestamp;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->timestamp = $_SERVER["REQUEST_TIME"];
    }

    public function urlToShortCode($url)
    {

        if (empty($url)) {
            throw new \Exception("Не получен адрес URL.");
        }

        if ($this->validateUrlFormat($url) == false) {
            throw new \Exception(
                "Адрес URL имеет неправильный формат.");
        }

        if (self::$checkUrlExists) {
            if (!$this->verifyUrlExists($url)) {
                throw new \Exception(
                    "Адрес URL не существует.");
            }
        }

        $shortCode = $this->urlExistsInDb($url);

        if ($shortCode == false) {
            $shortCode = $this->createShortCode($url);
        }

        $this->changeUrlLive(1, $shortCode);

        return $shortCode;
    }

    protected function validateUrlFormat($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL,
            FILTER_FLAG_HOST_REQUIRED);
    }

    protected function verifyUrlExists($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404);
    }

    protected function urlExistsInDb($url)
    {
        $query = "SELECT short_code FROM " . self::$table .
            " WHERE long_url = :long_url LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["short_code"];
    }

    protected function createShortCode($url)
    {
        $id = $this->insertUrlInDb($url);
        $shortCode = $this->convertIntToShortCode($id);
        $this->insertShortCodeInDb($id, $shortCode);
        return $shortCode;
    }

    protected function insertUrlInDb($url)
    {
        $query = "INSERT INTO " . self::$table .
            " (long_url, date_created) " .
            " VALUES (:long_url, :timestamp)";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url,
            "timestamp" => $this->timestamp
        );
        $stmnt->execute($params);

        return $this->pdo->lastInsertId();
    }

    protected function convertIntToShortCode($id)
    {
        $id = intval($id);
        if ($id < 1) {
            throw new \Exception(
                "ID не является некорректным целым числом.");
        }

        $length = strlen(self::$chars);
        // Проверяем, что длина строки
        // больше минимума - она должна быть
        // больше 10 символов
        if ($length < 10) {
            throw new \Exception("Длина строки мала");
        }

        $code = "";
        while ($id > $length - 1) {
            // Определяем значение следующего символа
            // в коде и подготавливаем его
            $code = self::$chars[fmod($id, $length)] .
                $code;
            // Сбрасываем $id до оставшегося значения для конвертации
            $id = floor($id / $length);
        }

        // Оставшееся значение $id меньше, чем
        // длина self::$chars
        $code = self::$chars[$id] . $code;

        return $code;
    }

    protected function insertShortCodeInDb($id, $code)
    {
        if ($id == null || $code == null) {
            throw new \Exception("Параметры ввода неправильные.");
        }
        $query = "UPDATE " . self::$table .
            " SET short_code = :short_code WHERE id = :id";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "short_code" => $code,
            "id" => $id
        );
        $stmnt->execute($params);

        if ($stmnt->rowCount() < 1) {
            throw new \Exception(
                "Строка не обновляется коротким кодом.");
        }

        return true;
    }

    public function shortCodeToUrl($code, $increment = true)
    {
        if (empty($code)) {
            throw new \Exception("Не задан короткий код.");
        }

//        $code = $code;

        if ($this->validateShortCode($code) == false) {
            throw new \Exception(
                "Короткий код имеет неправильный формат.");
        }

        $urlRow = $this->getUrlFromDb($code, true);

        if (empty($urlRow)) {
            throw new \Exception(
                "Короткий код не содержится в базе или истёк срок его действия");
        }

        if ($increment == true) {
            $this->incrementCounter($urlRow["id"]);
        }

        return $urlRow["long_url"];
    }

    protected function validateShortCode($code)
    {
        return preg_match("|[" . self::$chars . "]+|", $code);
    }

    protected function getUrlFromDb($code, $live = false)
    {
        $query = "SELECT id, long_url FROM " . self::$table .
            " WHERE short_code = :short_code " . ($live ? "AND url_live > " . time() : '') . " LIMIT 1";

        $stmt = $this->pdo->prepare($query);
        $params = array(
            "short_code" => $code
        );

        $stmt->execute($params);

        $result = $stmt->fetch();

        return (empty($result)) ? false : $result;
    }

    public function getUrlsFromDb()
    {
        $query = "SELECT * FROM " . self::$table;
        $stmt = $this->pdo->prepare($query);

        $stmt->execute();

        $result = $stmt->fetchAll();

        return (empty($result)) ? false : $result;
    }

    protected function incrementCounter($id)
    {
        $query = "UPDATE " . self::$table .
            " SET counter = counter + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);

        $userAgent = $this->getAgentFromDb($id);

        if ($userAgent == false) {
            $bArray = $this->getBrowser();
            $browserName = $bArray["name"] . " " . $bArray["version"];
            $this->insertAgentInDb($id, $browserName);
        } else {
            $this->incrementACounter($userAgent["id"]);
        }

        $userCountry = $this->getCountryFromDb($id);

        if ($userCountry == false) {
            $this->insertCountryInDb($id, $this->getCountry());
        } else {
            $this->incrementCCounter($userCountry["id"]);
        }
    }

    protected function incrementACounter($id)
    {
        $query = "UPDATE users_agents SET a_counter = a_counter + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);
    }

    protected function incrementCCounter($id)
    {
        $query = "UPDATE country SET c_counter = c_counter + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);
    }

    protected function getAgentFromDb($id)
    {
        $bArray = $this->getBrowser();

        $browserName = $bArray["name"] . " " . $bArray["version"];

        $query = "SELECT id, agent FROM users_agents WHERE url_id = :id AND agent = :agent LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id,
            "agent" => $browserName
        );
        $stmt->execute($params);

        $result = $stmt->fetch();

        return (empty($result)) ? false : ["id" => $result["id"], "user_agent" => $browserName];
    }

    protected function getCountryFromDb($id)
    {
        $country = $this->getCountry();

        $query = "SELECT * FROM country WHERE url_id = :id AND country = :country LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id,
            "country" => $country
        );
        $stmt->execute($params);

        $result = $stmt->fetch();

        return (empty($result)) ? false : ["id" => $result["id"], "country" => $country];
    }

    protected function insertAgentInDb($url_id, $user_agent)
    {
        $query = "INSERT INTO users_agents (url_id, agent, a_counter) " .
            " VALUES (:url_id, :agent, 1)";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "url_id" => $url_id,
            "agent" => $user_agent
        );
        $stmnt->execute($params);

    }

    protected function insertCountryInDb($url_id, $country)
    {
        $query = "INSERT INTO country (url_id, country, c_counter) " .
            " VALUES (:url_id, :country, 1)";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "url_id" => $url_id,
            "country" => $country
        );
        $stmnt->execute($params);
    }

    public function changeUrlLive($url_live, $short_code)
    {
        $result = $this->getUrlFromDb($short_code);

        $timestamp = strtotime("+" . $url_live . " hour");

        $query = "UPDATE " . self::$table .
            " SET url_live = :live WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $result["id"],
            "live" => $timestamp
        );
        $stmt->execute($params);

        return $result;
    }

    public function getFromCode($code)
    {
        $query = "SELECT * FROM " . self::$table .
            " WHERE short_code = :short_code LIMIT 1";

        $stmt = $this->pdo->prepare($query);
        $params = array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetch();

        return (empty($result)) ? false : $result;
    }

    public function getFromCodeAgents($code)
    {
        $query = "SELECT agent, a_counter FROM " . self::$table .
            " su, users_agents ua WHERE su.id = ua.url_id AND su.short_code = :short_code";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetchAll();

        return (empty($result)) ? false : $result;
    }

    public function getFromCodeCountries($code)
    {
        $query = "SELECT country, c_counter FROM " . self::$table .
            " su, country c WHERE su.id = c.url_id AND su.short_code = :short_code";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetchAll();

        return (empty($result)) ? false : $result;
    }

    function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";
        // First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }
        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }
        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }
        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }
        return array(
            'userAgent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        );
    }

    function getCountry()
    {
        $user_ip = getenv('REMOTE_ADDR');
        $geo = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=$user_ip"));
        $country = $geo["geoplugin_countryName"];

        return (!$country) ? "Uzbekistan" : $country;
    }

}