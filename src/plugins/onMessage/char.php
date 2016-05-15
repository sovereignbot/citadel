<?php

namespace Sovereign\Plugins;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;
use Sovereign\Lib\Config;
use Sovereign\Lib\cURL;
use Sovereign\Lib\Db;
use Sovereign\Lib\Permissions;
use Sovereign\Lib\ServerConfig;
use Sovereign\Lib\Settings;
use Sovereign\Lib\Users;

class char extends \Threaded implements \Collectable
{
    /**
     * @var Message
     */
    private $message;
    /**
     * @var Discord
     */
    private $discord;
    /**
     * @var Logger
     */
    private $log;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Db
     */
    private $db;
    /**
     * @var cURL
     */
    private $curl;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var Permissions
     */
    private $permissions;
    /**
     * @var ServerConfig
     */
    private $serverConfig;
    /**
     * @var Users
     */
    private $users;
    /**
     * @var \WolframAlpha\Engine
     */
    private $wolframAlpha;
    /**
     * @var int
     */
    private $startTime;

    public function __construct($message, $discord, $log, $config, $db, $curl, $settings, $permissions, $serverConfig, $users, $wolframAlpha, $startTime)
    {
        $this->message = $message;
        $this->discord = $discord;
        $this->log = $log;
        $this->config = $config;
        $this->db = $db;
        $this->curl = $curl;
        $this->settings = $settings;
        $this->permissions = $permissions;
        $this->serverConfig = $serverConfig;
        $this->users = $users;
        $this->wolframAlpha = $wolframAlpha;
        $this->startTime = $startTime;
    }

    public function run()
    {        // Most EVE players on Discord use their ingame name, so lets support @highlights
        $explode = explode(" ", $this->message->content);
        $name = isset($explode[1]) ? $explode[1] : "";
        $name = stristr($name, "@") ? str_replace("<@", "", str_replace(">", "", $name)) : $name;

        if (is_numeric($name)) // The person used @highlighting, so now we got a discord id, lets map that to a name
            $name = $this->db->queryField("SELECT nickName FROM users WHERE discordID = :id", "nickName", array(":id" => $name));


        $url = "http://rena.karbowiak.dk/api/search/character/{$name}/";
        $data = @json_decode($this->curl->get($url), true)["character"];
        if (empty($data))
            return $this->message->reply("**Error:** no results was returned.");

        if (count($data) > 1) {
            $results = array();
            foreach ($data as $char)
                $results[] = $char["characterName"];
            return $this->message->reply("**Error:** more than one result was returned: " . implode(", ", $results));
        }

        // Get stats
        $characterID = $data[0]["characterID"];
        $statsURL = "https://beta.eve-kill.net/api/charInfo/characterID/" . urlencode($characterID) . "/";
        $stats = json_decode($this->curl->get($statsURL), true);
        if (empty($stats))
            return $this->message->reply("**Error:** no data available");

        $characterName = @$stats["characterName"];
        $corporationName = @$stats["corporationName"];
        $allianceName = isset($stats["allianceName"]) ? $stats["allianceName"] : "None";
        $factionName = isset($stats["factionName"]) ? $stats["factionName"] : "None";
        $securityStatus = @$stats["securityStatus"];
        $lastSeenSystem = @$stats["lastSeenSystem"];
        $lastSeenRegion = @$stats["lastSeenRegion"];
        $lastSeenShip = @$stats["lastSeenShip"];
        $lastSeenDate = @$stats["lastSeenDate"];
        $corporationActiveArea = @$stats["corporationActiveArea"];
        $allianceActiveArea = @$stats["allianceActiveArea"];
        $soloKills = @$stats["soloKills"];
        $blobKills = @$stats["blobKills"];
        $lifeTimeKills = @$stats["lifeTimeKills"];
        $lifeTimeLosses = @$stats["lifeTimeLosses"];
        $amountOfSoloPVPer = @$stats["percentageSoloPVPer"];
        $ePeenSize = @$stats["ePeenSize"];
        $facepalms = @$stats["facepalms"];
        $lastUpdated = @$stats["lastUpdatedOnBackend"];
        $url = "https://beta.eve-kill.net/character/" . $stats["characterID"] . "/";
        $msg = "```characterName: {$characterName}
corporationName: {$corporationName}
allianceName: {$allianceName}
factionName: {$factionName}
securityStatus: {$securityStatus}
lastSeenSystem: {$lastSeenSystem}
lastSeenRegion: {$lastSeenRegion}
lastSeenShip: {$lastSeenShip}
lastSeenDate: {$lastSeenDate}
corporationActiveArea: {$corporationActiveArea}
allianceActiveArea: {$allianceActiveArea}
soloKills: {$soloKills}
blobKills: {$blobKills}
lifeTimeKills: {$lifeTimeKills}
lifeTimeLosses: {$lifeTimeLosses}
percentageSoloPVPer: {$amountOfSoloPVPer}
ePeenSize: {$ePeenSize}
facepalms: {$facepalms}
lastUpdated: $lastUpdated```
For more info, visit: $url";
        $this->message->reply($msg);
    }

    public function information()
    {
        return (object)array(
            "description" => "Fetches data from EVE-KILL about a character",
            "usage" => "<characterName>",
            "permission" => 1//1 is everyone, 2 is only admin
        );
    }
}