# Running Local Server on Windows

Altis Local Server supports running on Windows 10 through the use of Docker Desktop and Windows Subsystem for Linux (WSL). Support
for Hyper-V is required, which is available on Windows 10 Professional and Enterprise editions.

**Note:** Hyper-V virtualization and networking is not available in Windows 10 Home edition. If you are using Windows 10 Home
Edition you will need to use [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/tutorials/wsl-containers) (Windows Subsystem for
Linux, version 2).

Follow these steps to set up Local Server on Windows for the first time:

* [Enable virtualization](#enable-virtualization)
* [Install WSL2](#install-wsl2)
* [Install Altis prerequisites](#install-altis-prerequisites)
* [Install Local Server](#install-local-server)

## Enable virtualization

Virtualization and Hyper-V must be enabled at the system level, including within your BIOS and Windows. This requires hardware
support for virtualization in your CPU.

For Intel-based machines, this option may be called "Intel Virtualization Technology", "Intel VT-x", or "Virtualization".

For AMD-based machines, this option may be called "AMD-V", "SVM Mode" ("Secure Virtual Machine"), or "Virtualization".

If virtualization is not enabled, you may see the following error when starting WSL later:

```sh
Please enable the Virtual Machine Platform Windows feature and ensure virtualization is enabled in the BIOS.
```

Please note that Altis Support is unable to assist with enabling virtualization within your system. Consult your computer
manufacturer for further assistance.

## Install WSL2

Once virtualization is enabled, you can install Windows Subsystem for Linux. This will install a lightweight virtual machine from
which you can run Altis commands, including the Composer installation commands.

Follow the [official Microsoft guide for installing WSL2](https://docs.microsoft.com/en-us/windows/wsl/install-win10).

While any Linux distribution will work, we recommend Ubuntu for first-time users.

Please note that Altis Support is unable to assist with installation of WSL2. Consult official Microsoft support channels for
further assistance.

Once WSL is installed, you will have an app on your desktop named after the Linux distribution you have installed, i.e. "Ubuntu".
Opening this app will open a command-line terminal inside the WSL environment, which is a full Linux environment.

**Important:** All further steps and commands must be run inside your WSL environment (i.e. the "Ubuntu" app), unless otherwise
noted. Any commands in the Altis documentation must be run inside your WSL environment.

## Install Altis prerequisites

In order to perform further steps and use the Altis command line tools, you will need to install PHP and Composer within WSL.

This depends on your Linux distribution. For Ubuntu, you can install PHP via the apt package manager:

```sh
$ sudo apt update

Hit:1 http://archive.ubuntu.com/ubuntu jammy InRelease
Get:2 http://archive.ubuntu.com/ubuntu jammy-updates InRelease [128 kB]
...                                                        
All packages are up to date.

$ sudo apt install php-cli php-curl php-zip php-xml

Reading package lists... Done
Building dependency tree       
...
After this operation, 15.8 MB of additional disk space will be used.
Do you want to continue? [Y/n]
```

Once PHP has been installed, [install Composer following the official instructions](https://getcomposer.org/download/).

You will also need to install Docker Desktop for Windows. Download and install Docker
at [https://www.docker.com/get-started](https://www.docker.com/get-started). This must be installed within Windows, not inside your
WSL environment; Docker will create another lightweight virtual machine which lives alongside your WSL environment.

To verify you have set up all these tools correctly, close your WSL environment and reopen it, then run:

```sh
$ docker --version
Docker version 27.0.3, build 7d4bcd8

$ php --version
PHP 8.1.2-1ubuntu2.18 (cli) (built: Jun 14 2024 15:52:55) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.1.2, Copyright (c) Zend Technologies
    with Zend OPcache v8.1.2-1ubuntu2.18, Copyright (c), by Zend Technologies

$ composer --version
Composer 2.2.6 2022-02-04 17:00:38
```

You should see version numbers for each component printed to your terminal.

## Install Local Server

You now have a working Linux development environment within your Windows system. With this development environment, you can use any
of the regular setup commands, as well as other commands specified in the documentation.

It is important that any commands you run are run within your WSL environment (i.e. the "Ubuntu" app).

For further setup and running instructions, follow the [Local Server installation guide](README.md#installing).

## Recommendations

### Using VS Code with WSL2

For the best performance during development, we recommend using Visual Studio Code with
the [Remote - WSL extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-wsl). This allows your
files to live within the Linux filesystem, improving performance of your Local Server environment, while retaining full use of
Visual Studio Code's functionality.

## Troubleshooting

For issues related to WSL2 setup, consult
the [WSL troubleshooting guide](https://docs.microsoft.com/en-us/windows/wsl/install-win10#troubleshooting-installation). For Local
Server issues, consult the [troubleshooting guide](./troubleshooting.md).

Altis Support can assist with development environment setup, but is unable to assist with machine-level settings including enabling
virtualization or installing WSL2.
