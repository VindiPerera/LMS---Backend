Vendored, namespaced copy of Agora's official token builder (MIT, see LICENSE) from
https://github.com/AgoraIO/Tools — DynamicKey/AgoraDynamicKey/php/src. Only mechanical edits:
added `namespace App\Support\Agora;` and removed the relative `require_once` lines (these files are
loaded by App\Services\AgoraTokenService instead). Do not hand-edit the signing logic.

Second mechanical edit: AccessToken2::parse() maps service types to class names via bare
strings ("ServiceRtc"), which a namespace breaks — those are now `ServiceRtc::class` etc.
