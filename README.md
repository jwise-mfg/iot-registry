# IOT Registry

This is a simple IOT registry for finding headless devices. It uses a client-side script against a very basic server-side registry.

You can run it on your own domain, or review the source at https://github.com/jwise-mfg/iot-registry

When run on a device, the script "checks in" with your web server and reports some basic stats about the device its running on, to help with finding and administering headless IOT devices (that run some flavor of Linux).

## Installation on Server

Clone this repo to your web server of choice.

The web server needs PHP 7 or up.

Both client and server assume this service will be at the root of the domain (or subdomain), so https://iot.yourserver/ would work, but https://www.yourserver/iot would not.

You probably want to secure the `cache` and `view` folders (which is done differently in different web servers, and can't be covered here.)

If you don't want to display this README as the home page of your service, add a file called home.html to the root (or replace the index.php)

## Installation on Client

The intent is to install scripts as part of imaging/provisioning a headless device, then when they come up on a network later, you'll be able to find their IP address.

It works best if the device has systemd, but you can also manage the service with cron.

The checkin script does the work on the device, and should be scheduled to run when the network is up. You can fetch the latest script at any time by downloading from `/scripts/checkin` on the server where you deployed this repo. Using that path will modify the script during download to include the hostname of *your* server. 

### Manual Start

- wget https://yourserver/scripts/checkin
- chmod +x checkin
- ./checkin

The checkin will execute against "youserver"

### Example installation:

- wget https://yourserver/scripts/checkin
- chmod +x checkin
- ./checkin --install-service

This results in a systemd service that runs after boot, once the network is up. If systemd is not found, a suggested cron line is returned.

## Cautions and Caveats

I built this for test and demo environments. If you choose to use this in prod, you should be aware of the implications:

- :smiley: You can view the state of a fleet of devices from a simple web page anywhere!
- :grimacing: You can potentially leak information about a fleet of devices to the Internet that could be used in an attack!

Always use HTTPS for this service. The script will not run properly if it tries to call HTTP, and using HTTPS helps prevent man-in-the-middle snooping.

There is no warranty or indemnification either explicit or implied from the author and publisher for any use of this code. Use appropriately and at your own risk.