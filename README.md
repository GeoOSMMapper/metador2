# MeTaDor2

MeTaDor2 is a metadata editor for creating metadata according to the INSPIRE and GDI-DE implementing rules.

![Image of MeTaDor2](https://raw.githubusercontent.com/WhereGroup/metador2/master/app/Resources/doc/images/metador_screenshot.png)

MeTaDor2 is a web-application written in PHP and uses the [Symfony](https://github.com/symfony/symfony) framework. The application allows you to create any metadata-document not only ISO- or INSPIRE-based. It allows the export of the metadata to XML or PDF according to your own rules. MeTaDor2 is able to import OGC-WMS Capabilities to create metadata documents based on their information.

The features of MetaDor2 are:

* create and edit metadata documents according to the INSPIRE Technical Guidelines and in consideration of the GID-DE architecture,
* create and edit metadata in your own metadata structure,
* storage of address-data for the contact-elements,
* user- and group-management,
* export metadata into a directory for file-harvesting,
* use already available metadata as a template,
* provide a helptext for every metadata-element,
* import XML metadata documents
* import WMS Capabilities to create metadata-documents for this WMS services.


## Usage of the helptexts

Every metadata-element allows to associate a helptext to help the user filling out the forms.

![MeTaDor2 helptext](https://raw.githubusercontent.com/WhereGroup/metador2/master/app/Resources/doc/images/metador_helptext.png)

We provide some helptext for the dataset and service metadata structure. To import the helptext, type `app/console metador:import:helptext -f /app/Resources/doc/metador-helptext.json` from the metador2-directory. To export your helptexts, type `app/console metador:export:helptext -f /tmp/hilfetext.json`.

If you adjusted the metadata-structure, create the helptexts and export them. To edit the helptexts for an element, put your user into the group `ROLE_HELPTEXT_ADMIN`.

## View the metadata as XML or PDF

Each metadata document can be shown as XML. Use an URL like this: http://localhost/metador2065/metador/xml/1. Also a PDF view is possible with an URL like this: http://localhost/metador2065/metador/pdf/1. You can even look at the object that is based on the XML and PDF export: http://localhost/metador2065/metador/obj/1.

To adjust the PDF display, change the file /Resources/views/pdf.html.twig. To configure the XML-output change the file Resources/views/Dataset/dataset.xml.twig or Resources/views/Service/service.xml.twig.

Please keep in mind that you better create your own bundle than to change the source of the MeTaDor core.


## Extend with a CSW broker

MeTaDor2 itself does not contain a CSW interface but can be easily extended by a CSW broker like [GeoNetwork](https://github.com/geonetwork/core-geonetwork) or [deegree](https://github.com/deegree/deegree3). The metadata written in MeTaDor2 is published as XML into a folder and can be harvested by the CSW broker to supply access via the OGC-CSW protocol. Simply add into the parameters.yml the line `metador.export.path: /srv/metadata/` and set which metadata documents should be exported to that folder.

## Future development

The current version of MeTaDor is [2.0.6.5](https://github.com/WhereGroup/metador2/releases).

The [2.0 branch](https://github.com/WhereGroup/metador2/tree/2.0) contains the development of this 2.0.x versions. The [2.1 branch](https://github.com/WhereGroup/metador2/tree/2.1) contains the code of the upcoming 2.1.x version.

Version 2.1 of MeTaDor2 will contain a plugin system so that it will be easier to include, activate or deactivate your own metadata-profiles. Plugins may also contain different themes, other import or export rules or other implementations or extensions.

![MeTaDor2.1 plugins](https://raw.githubusercontent.com/WhereGroup/metador2/master/app/Resources/doc/images/metador21_plugins.png)

## Requirements / Installation / Updates / Changelog

[Required](app/Resources/doc/required.md)  
[Installation](app/Resources/doc/installation.md)  
[Changelog](app/Resources/doc/changelog.md)  
[Update](app/Resources/doc/update.md)  


## Issues

Please report issues here at Github on the [MeTaDor2 issue page](https://github.com/WhereGroup/metador2/issues).
