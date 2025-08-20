#! /bin/bash
cd /home/u942107814/domains/innovineschoolshop.com/innovine
for arg; do
  case $arg in
    --transaction)
      /opt/alt/php83/usr/bin/php bin/console IS:Sync transaction
      ;;
    --status)
      /opt/alt/php83/usr/bin/php bin/console IS:tracking updateStatus
      ;;
    --tax)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchTaxes
      ;;
    --contact)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchContacts
      ;;
    --contact-address)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchContactsWithAddress
      ;;
    --warehouse)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchWarehouses
      ;;
    --group)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchItemGroup
      ;;
    --item)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchItems
      ;;
    --stock)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchStocks
      ;;
    --all)
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchTaxes
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchContacts
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchWarehouses
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchItemGroup
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchItems
      /opt/alt/php83/usr/bin/php bin/console IS:Zoho fetchStocks
      ;;
  esac
done
