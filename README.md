This is a collection of open-source companion tools for the
[EnvisaLink 4](https://www.eyezon.com/evl4.php) TCP-IP based bus-level interface
to DSC PowerSeries alarm systems.

The core of these tools is the EnvisaLink server (evlsrv). It connects to the
EnvisaLink 4 module through the TPI (third party interface) and maintains a
complete snapshot of the panel state.

While the EnvisaLink 4 module itself allows a single TPI connection at any given
time, the server acts as a proxy and allows multiple clients to interact with
the EnvisaLink module simultaneously.

The server has a built-in web interface that mimics a standard wall keypad, such
as the PK5500. This makes it easier to control/configure the alarm system, by
using a computer or a mobile device.
