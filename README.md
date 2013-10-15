biz.jmaconsulting.ode
=====================

Outbound Domain Enforcement for CiviCRM

This extension is designed to preserve the email reputation of your server and its IP by ensuring that all outbound 
emailis sent from an address with the same domain as the System-generated Mail Settings From Email Address configured 
at Administer > Communications > Organization Address and Contact Info (civicrm/admin/domain?action=update&reset=1).

By default, CiviCRM uses the primary email address of a logged in user when sending an email, which may be configured to
a different domain than the From Email Address just mentioned. It also allows From email addresses with other domains to 
be configured at Administer > Communications > From Email Addresses 
(civicrm/admin/options/from_email_address?group=from_email_address&reset=1), and at Administer > CiviMail > 
From Email Addresses (civicrm/admin/options/from_email?group=from_email_address&reset=1).

If the server running CiviCRM is not authorized to send mail on behalf of a From address, perhaps because of a very 
tight SPF policy, it can lead to the server and / or its IP being blacklisted as a spammer. 

It is fairly safe to assume that whoever initially configures CiviCRM will set up a From Email Address in the 
System-generated Mail Settings that is not barred from sending from CiviCRM's server. However, staff users authorized to
send mail may inadvertently have a different domain used in their primary email and not realize using it will create
problems. Similarly, they may create and use another From Email Address. 

This extension filters the From Email Address options provided to users as they are about to send an email. Only ones 
that have the same domain as the System-generated Mail Settings From Email Address are exposed, with the others 
suppressed.

When a From Email option is suppressed, the following message is displayed to users: 'The Outbound Domain Enforcement 
extension has prevented the following From Email Address option(s) from being used as it uses a different domain than 
the System-generated Mail Settings From Email Address configured at Administer > Communications > Organization Address 
and Contact Info: email.with@different.domain.org, email.with@different.domain2.org.'

