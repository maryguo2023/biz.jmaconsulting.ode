biz.jmaconsulting.ode
=====================

Outbound Domain Enforcement for CiviCRM
---------------------------------------

This extension is designed to preserve the email reputation of your server and its IP by ensuring that all outbound 
email is sent from an address with the same domain as the System-generated Mail Settings From Email Address configured 
at Administer > Communications > Organization Address and Contact Info (civicrm/admin/domain?action=update&reset=1).

The need for this extension
---------------------------

It is fairly safe to assume that whoever initially configures CiviCRM will set up a From Email Address in the 
System-generated Mail Settings that is not barred from sending from CiviCRM's server. However, staff users authorized to
send mail may inadvertently have a different domain used in their primary email and not realize using it will create
problems. Similarly, they may create and use another From Email Address. 

The details
-----------

By default, CiviCRM uses the primary email address of a logged in user when sending an email, which may be configured to
a different domain than the From Email Address just mentioned. It also allows From email addresses with other domains to 
be configured at Administer > Communications > From Email Addresses 
(civicrm/admin/options/from_email_address?group=from_email_address&reset=1), and at Administer > CiviMail > 
From Email Addresses (civicrm/admin/options/from_email?group=from_email_address&reset=1).

If the server running CiviCRM is not authorized to send mail on behalf of a From address, perhaps because of a very 
tight SPF policy, it can lead to the server and / or its IP being blacklisted as a spammer. 

This extension filters the From Email Address options provided to users as they are about to send an email. Only ones 
that have the same domain as the System-generated Mail Settings From Email Address are exposed, with the others 
suppressed.

When a From Email option is suppressed, the following message is displayed to users: 'The Outbound Domain Enforcement 
extension has prevented the following From Email Address option(s) from being used as it uses a different domain than 
the System-generated Mail Settings From Email Address configured at Administer > Communications > Organization Address 
and Contact Info: email.with@different.domain.org, email.with@different.domain2.org.'

NB: Here are the forms that allow users to select From emails that will be filtered and/or validated:
1. The 3rd step of the CiviMail wizard
2. Individual emails being sent from Contact Summary page, Actions > Send email.
3. Individual emails being sent from Contact Summary page Activities tab, and for new activity select Send an email. 
4. Search results page, after selecting contacts with emails, changing action to Send Email to Contacts and clicking Go.
5. Validate From Address fields on create or updates of the Manage Contribution Pages, Thank you and receipting tab.
6. Validate From Address fields on Manage Events > Online Registration tab.
7. If the Grant Application extension is installed, then the From Address fields on the Receipting tab.
8. From Address field on Add/Edit Schedule Reminder.
9. If Send Receipt is selected on Batch Entry for Contribution & Batch Entry for Membership.   
10. When notification emails are selected for Profiles.
11. When TO and CC emails are selected in email delivery settings.
12. The Organization Address and Contact Info form
13. Various backend component creations. registrations, signups etc.


Installation
============

1. As part of your general CiviCRM installation, you should set a CiviCRM Extensions Directory at Administer >> System Settings >> Directories.
2. As part of your general CiviCRM installation, you should set an Extension Resource URL at Administer >> System Settings >> Resource URLs.
3. Navigate to Administer >> System Settings >> Manage Extensions.
4. Beside Outbound Domain Enforcement, click Install.
