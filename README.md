# Replacement weather service for `info.gigaset.net`

This project allows to host your own mini-service for providing weather forecasts to your Gigaset IP handset (e.g. Gigaset C430A Go) even though, Siemens has discontinued running `info.gigaset.net` which was previously providing the data-source.

This replacement is currently extremely limited and only provides weather forecast data for a single location that you can configure. Configuration is done server-side and not via the handset (as previously).

This project is designed to run on ancient PHP5 because that is what my old trusty QNAP TS-109 Pro II is still on.

Of course, it would be really easy to reimplement this in `node`, `python` or whatever else, you would prefer.

Let me know what you think and send PRs for fixes and extensions!

## TL;DR

This project will:
- redirect `info.gigaset.net` to your own HTTP server on your local net
- provide weather data via two URLs that are used by the handset to retrieve "Info Center" data:
  - `http://info.gigaset.net/info/menu.jsp?lang=2&tz=120&mac=7C2F80XXXXXX&cc=49&handsetid=XXXXXXXXXX&provid=11`\
    This one is retrieved when accessing the "Info Center" from the "Extras" menu.
  - `http://info.gigaset.net/info/request.do?lang=2&tz=120&tick=true&mac=7C2F80XXXXXX&cc=49&provid=11&handsetid=XXXXXXXXXX`\
    This one is retrieved when data is retrieved for the standby screen scrolling text and when "Info Center" is used as the screen-saver. The `tick=true` parameter is sometimes present, sometimes not. I did not dig deeper.

## First step: Register a free account on OpenWeatherMap

Go to the OpenWeatherMap [signup](https://home.openweathermap.org/users/sign_up) page and create an account. Then, go to your [API keys](https://home.openweathermap.org/api_keys) page and copy your key for the next step.

## Second step: Set up the service

Copy the contents of this repository to a directory served as `http://<yourserver>/info`. In my case, this would be `/Qweb/info/` on the NAS.

To make your `apache` server run the provided scripts fine, add something like this to your `apache` configuration file, `/usr/local/apache/conf/apache.conf` in my case:

```apache
<Directory "/share/Qweb/info">
    DirectoryIndex menu.jsp
    Order deny,allow
    Deny from all
    Allow from localhost
    Allow from 192.168.10.0/24
	AddType application/x-httpd-php .jsp .do
	SetEnv OPENWEATHERMAP_API_KEY <key from step 1 here>
	SetEnv CITY "Berlin"
	SetEnv LATITUDE 52.52437
	SetEnv LONGITUDE 13.41053
</Directory>
```

Obviously, replace `192.168.10.0` with your local IP network and the `SetEnv` lines according to your situation.

Verify and activate your configuration:
```term
# /usr/local/apache/bin/apachectl configtest
# /usr/local/apache/bin/apachectl restart
```

Now, try and go to `http://<yourserver>/info`. You should see something like this:

<div style="overflow:scroll; height:12rem">
  <p style='text-align:center'>Do, 23.05.2024<br/>19,3/23,6°C/0 mm<br/>Bedeckt</p><p style='text-align:center'>Fr, 24.05.2024<br/>17,5/24,8°C/0 mm<br/>Bedeckt</p><p style='text-align:center'>Sa, 25.05.2024<br/>14,2/24,2°C/5 mm<br/>Leichter Regen/Bed.</p><p style='text-align:center'>So, 26.05.2024<br/>13,7/24,8°C/1 mm<br/>Leichter Regen/Mäßig bew.</p><p style='text-align:center'>Mo, 27.05.2024<br/>14,3/25,7°C/0 mm<br/>Bed./Überw. bew.</p><p style='text-align:center'>Di, 28.05.2024<br/>13,2/16,6°C/4 mm<br/>Leichter Regen</p>
</div>

Also, check `http://<yourserver>/info/menu.jsp` and `http://<yourserver>/info/request.do` which should show the same thing.

## Third step: Redirect your Gigaset phone to your own server

This very much depends on what kind of router you have. It is easy for routers that provide their own (caching) DNS service like e.g. OpenWRT. Also, if you can manually set up a mapping of host-names to IP addresses, you might get lucky. The key part is to make the DNS server that is configured in your Gigaset base station (probably via DHCP) to resolve:

    info.gigaset.net -> <your server IP>

In OpenWRT, you can set this up at `https://<your-router>/cgi-bin/luci/admin/network/dhcp` in the _Hostnames_ tab.


## References

I used information from

- https://www.ip-phone-forum.de/threads/gigaset-infodienst-selbst-gemacht.174719/ (thanks to [VoIPMaster](https://www.ip-phone-forum.de/members/voipmaster.95683/))
- https://copyandpastecode.blogspot.com/2008/08/siemens-s685ip-s68h.html (thanks to [Jon Bright](https://www.blogger.com/profile/13465823659620242219))
- and in particular:  http://www.ensued.net/request.do (coded in ruby)

See the latter for more ideas on how to include RSS updates and public transport information.
