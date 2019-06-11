# IPS Hilfe Module Backup & Restore
**Benötigt** IP-Symcon Version **4.3** oder größer 

Diese Module dient zur schnellen Zwischenspeicherung der Konfiguration einer Instance um diese nach dem löschen wiederherzustellen. 
Eben mal eine Instance löschen, dann neu erstellen, dann wieder Konfigurieren kann **nervig** sein, besonders beim **Entwickeln**.

**Module Backup & Restore** bietet eine einfache Möglichkeit die zu vereinfachen und kann helfen:
- eine Konfiguration, von einer in eine neue neue Instance vom gleichen Type, zu übertragen.
- Bei bedarf beliebig viele neue Instanzen vom gleichen Typ erstellen bzw duplizieren.
- beim löschen eines Moduls und erneutem Installieren, die Konfiguration zwischenspeichern
- und für alles was einem noch so einfällt

# Update 1.0 => 1.1
- Erweitert um bis zu 5 Konfigurationen zu speichern 
- Erweitert um Sicherungen zu löschen
 
5 ist durch die private Variable **$MaxBackups**=5 im Modul definiert und kann erhöht werden, jedoch geht jede Speicherung zu lasten der größe der settings.json da die Sicherungsdaten fest hinterlegt werden um bei einem Systemneustart noch verfügbar zu sein!

um Datenmüll zuvermeiden empfiehlt es sich, nach dem Restore nicht benötigte Sicherungen wieder zu entfernen.
 
Um ein **versehentliches** löschen einer Sicherung im Wiederherstellungsmodus zu vermeiden, ist das löschen von Sicherungen nur im Modus "**Sicherung**" möglich.
 
Viel Spass ;-)