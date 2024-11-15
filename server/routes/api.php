<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

trait EnumToArray
{

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }

}

enum Providers: string {
    use EnumToArray;
    case ONE337X = '1337x';
    case BITSEARCH = 'bitsearch';
    case TORLOCK = 'torlock';
    case KNABEN = 'knaben';
    case TGX = 'torrentgalaxy';
    case MAGNETDL = 'magnetdl';
    case TPB = 'thepiratebay';
    case GLO = 'glotorrents';
    case LIME = 'limetorrents';
    case PIRATEIRO = 'pirateiro';
    case NYAA = 'nyaa';
    case ANIDEX = 'anidex';
    case TOSHO = 'animetosho';
    case TORRENTZ2 = 'torrentz2';
    case GK = 'gktorrent';
    case RUTOR = 'rutor';
    case YTS = 'yts';
}

Route::get('/ip', function () {
    $response = Http::get('https://api.ipify.org/?format=json')->json();
    return response()->json($response);
});

Route::get('/torrent', function(Request $request) {
    $query = $request->get("query");
    $page = $request->get("page");

    $allowed_page_navigation = [
        Providers::ONE337X->value,
        Providers::BITSEARCH->value,
        Providers::KNABEN->value,
        Providers::RUTOR->value,
        Providers::LIME->value,
        Providers::NYAA->value,
        Providers::YTS->value
    ];

    $providers = $request->get("providers") ?? Providers::values();
    $torrents = [];

    foreach ($providers as $provider) {
        $pg = 1;
        if (array_search($provider, $allowed_page_navigation)) {
            $pg = $page;
        }

        $movies = [];

        if ($page > 1 && $pg === 1) {
            continue;
        }

        if ($provider === Providers::YTS->value) {
            try {
                $movies = search_yts($query, $pg);
            } catch (Exception $e) {
                \Log::error(
                    "Failed to retrieve torrents for $provider and $query - page $pg".$e->getMessage(),
                    ["stack" => $e->getTrace()]
                );
            }
        } else {
            try {
                $movies = retrieve_torrents($provider, $query, $pg);
            } catch (Exception $e) {
                \Log::error(
                    "Failed to retrieve torrents for $provider and $query - page $pg".$e->getMessage(),
                    ["stack" => $e->getTrace()]
                );
            }
        }

        $torrents = [
            ...$torrents,
            ...$movies
        ];
    }

    return response()->json([
        "status" => "SUCCESS",
        "torrents" => $torrents
    ]);
});

function search_yts(string $query, string $page) {
    $response = Http::get("https://yts.mx/api/v2/list_movies.json?query_term=$query&page=$page")->json();

    if (!isset($response["data"]) || !isset($response["data"]["movies"])) {
        return response()->json(["movies" => []]);
    }

    $movie_list = [];
    foreach ($response["data"]["movies"] as $movie) {
        $title = $movie["title"];

        foreach ($movie["torrents"] as $torrent) {
            $movie_list[] = [
                "title" => "$title " . $torrent["quality"],
                "hash" => $torrent["hash"],
                "magnet" => build_magnet($torrent["hash"], $title),
                "size" => $torrent["size"],
            ];
        }
    }

    return $movie_list;
}

function retrieve_torrents(string $provider, string $query, string $page) {
    $response = Http::post("http://127.0.0.1:35000/torrents/api/v1/$provider", [
        "search" => $query,
        "page" => $page
    ])->json();

    if ($response["statusCode"] != 200) {
        return [];
    }

    $torrent_list = [];
    foreach ($response["torrents"] as $torrent) {
        $title = $torrent["Name"];
        $hash = "";
        $magnet = $torrent["Magnet"];
        $pattern = '/magnet:\?xt=urn:btih:([a-fA-F0-9]+)/';
        $size = $torrent["Size"];

        if (preg_match($pattern, $magnet, $matches)) {
            $hash = $matches[1];
        }

        $torrent_list[] = [
            "title" => $title,
            "hash" => $hash,
            "magnet" => $magnet,
            "size" => $size
        ];
    }

    return $torrent_list;
}

function build_magnet(string $hash, string $name): string
{
    $trackers = [
        "udp://open.demonii.com:1337/announce",
        "udp://tracker.openbittorrent.com:80",
        "udp://tracker.coppersurfer.tk:6969",
        "udp://glotorrents.pw:6969/announce",
        "udp://tracker.opentrackr.org:1337/announce",
        "udp://torrent.gresille.org:80/announce",
        "udp://p4p.arenabg.com:1337",
        "udp://tracker.leechers-paradise.org:6969",
        "udp://tracker.opentrackr.org:1337/announce",
        "http://tracker.opentrackr.org:1337/announce",
        "udp://open.tracker.cl:1337/announce",
        "udp://open.demonii.com:1337/announce",
        "udp://open.stealth.si:80/announce",
        "udp://tracker.torrent.eu.org:451/announce",
        "udp://exodus.desync.com:6969/announce",
        "udp://tracker-udp.gbitt.info:80/announce",
        "udp://explodie.org:6969/announce",
        "https://tracker.tamersunion.org:443/announce",
        "http://tracker1.bt.moack.co.kr:80/announce",
        "udp://tracker1.myporn.club:9337/announce",
        "udp://tracker.tiny-vps.com:6969/announce",
        "udp://tracker.bittor.pw:1337/announce",
        "udp://tracker.0x7c0.com:6969/announce",
        "udp://opentracker.io:6969/announce",
        "udp://new-line.net:6969/announce",
        "udp://leet-tracker.moe:1337/announce",
        "udp://isk.richardsw.club:6969/announce",
        "udp://bt.ktrackers.com:6666/announce",
        "http://tr.kxmp.cf:80/announce",
        "udp://u.peer-exchange.download:6969/announce",
        "udp://ttk2.nbaonlineservice.com:6969/announce",
        "udp://tracker2.dler.org:80/announce",
        "udp://tracker.tryhackx.org:6969/announce",
        "udp://tracker.srv00.com:6969/announce",
        "udp://tracker.skynetcloud.site:6969/announce",
        "udp://tracker.qu.ax:6969/announce",
        "udp://tracker.jamesthebard.net:6969/announce",
        "udp://tracker.fnix.net:6969/announce",
        "udp://tracker.filemail.com:6969/announce",
        "udp://tracker.edkj.club:6969/announce",
        "udp://tracker.dler.org:6969/announce",
        "udp://tracker.deadorbit.nl:6969/announce",
        "udp://tracker.darkness.services:6969/announce",
        "udp://tracker.ccp.ovh:6969/announce",
        "udp://tamas3.ynh.fr:6969/announce",
        "udp://ryjer.com:6969/announce",
        "udp://run.publictracker.xyz:6969/announce",
        "udp://retracker01-msk-virt.corbina.net:80/announce",
        "udp://public.tracker.vraphim.com:6969/announce",
        "udp://p4p.arenabg.com:1337/announce",
        "udp://p2p.publictracker.xyz:6969/announce",
        "udp://open.u-p.pw:6969/announce",
        "udp://open.publictracker.xyz:6969/announce",
        "udp://open.dstud.io:6969/announce",
        "udp://open.demonoid.ch:6969/announce",
        "udp://odd-hd.fr:6969/announce",
        "udp://moonburrow.club:6969/announce",
        "udp://martin-gebhardt.eu:25/announce",
        "udp://jutone.com:6969/announce",
        "udp://evan.im:6969/announce",
        "udp://epider.me:6969/announce",
        "udp://d40969.acod.regrucolo.ru:6969/announce",
        "udp://bt2.archive.org:6969/announce",
        "udp://bt1.archive.org:6969/announce",
        "udp://amigacity.xyz:6969/announce",
        "udp://1c.premierzal.ru:6969/announce",
        "https://www.peckservers.com:9443/announce",
        "https://trackers.run:443/announce",
        "https://tracker.yemekyedim.com:443/announce",
        "https://tracker.renfei.net:443/announce",
        "https://tracker.pmman.tech:443/announce",
        "https://tracker.cloudit.top:443/announce",
        "https://tracker-zhuqiy.dgj055.icu:443/announce",
        "http://www.peckservers.com:9000/announce",
        "http://tracker2.dler.org:80/announce",
        "http://tracker.renfei.net:8080/announce",
        "http://tracker.qu.ax:6969/announce",
        "http://tracker.mywaifu.best:6969/announce",
        "http://tracker.ipv6tracker.org:80/announce",
        "http://tracker.files.fm:6969/announce",
        "http://tracker.edkj.club:6969/announce",
        "http://tracker.dler.org:6969/announce",
        "http://tracker.bt4g.com:2095/announce",
        "http://tracker-zhuqiy.dgj055.icu:80/announce",
        "http://t1.aag.moe:17715/announce",
        "http://t.overflow.biz:6969/announce",
        "http://ch3oh.ru:6969/announce",
        "udp://torrents.artixlinux.org:6969/announce",
        "udp://mail.artixlinux.org:6969/announce",
        "udp://ipv4.rer.lol:2710/announce",
        "udp://concen.org:6969/announce",
        "udp://bt.rer.lol:6969/announce",
        "udp://bt.rer.lol:2710/announce",
        "udp://aegir.sexy:6969/announce",
        "https://tracker.lilithraws.org:443/announce",
        "https://tracker.ipfsscan.io:443/announce",
        "https://tracker.gcrenwp.top:443/announce",
        "http://tracker1.itzmx.com:8080/announce",
        "http://bvarf.tracker.sh:2086/announce",
    ];

    $trackers_string = "";

    foreach ($trackers as $tracker) {
        $trackers_string .= "&tr=$tracker";
    }

    $encoded_name = urlencode($name);
    return "magnet:?xt=urn:btih:$hash&dn=$encoded_name$trackers_string";
}