Expansion Panel
===============

Provides integration with the Material Design [Expansion panel][1] surface. The
module offers the following:

  - A template to render generic expansion panels.
  - A field widget for entity reference fields. The information which is shown
    in the expansion panel "details" (the expanded area) is configurable through
    a view mode in the form settings. The "summary" (the title which is shown
    even when the panel is collapsed) is the title of the referenced entity.

This has been developed as a custom module because this depends heavily on the
current Joinup theme which is based on a customized legacy implementation of the
Material Design frontend framework. There is little value in making this a
contributed module because it is not usable outside of Joinup.


Showcase
--------

This is currently used to render the "Solution type" option in the solution edit
form.


Further reading
---------------

For more information see the following JIRA tickets which contain the business
use case, design iterations and functional evaluations:

   - [ISAICP-5757][2]: Improve UX for choosing EIRA Building Blocks
   - [ISAICP-5829][3]: Replace 'solution type' widget with Select2
   - [ISAICP-5340][4]: Help solution creators find correct EIRA Building Blocks

[1]: https://material-ui.com/components/expansion-panels/
[2]: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5757
[3]: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5829
[4]: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5340
