# Upload Expanded Knowledge Base to ElevenLabs (error-safe)

## Files to upload (Knowledge Base → Sources)
From `ragbot/knowledge/`:

1. `01-what-is-lipabyte.txt` (updated)
2. `02-accounts-and-registration.txt`
3. `03-marketplace-and-listings.txt`
4. `04-borrowing-workflow.txt`
5. `05-messages-and-chat.txt`
6. `06-reviews-and-ratings.txt`
7. `07-faq.txt` (updated)
8. `08-device-inquiries.txt` **(new)**
9. `09-navigation-and-activity.txt` **(new)**
10. `10-listings-snapshot-guide.txt` **(new)**
11. `11-listings-snapshot.txt` **(generated — listings & prices)**

Also paste updated instructions from:
`ragbot/AGENT_INSTRUCTIONS.txt`

## Generate / refresh listings snapshot
Local XAMPP DB:
```bat
c:\xampp\php\php.exe c:\xampp\htdocs\lipabyte\tools\export_listings_snapshot.php
```

Live InfinityFree DB:
```bat
c:\xampp\php\php.exe c:\xampp\htdocs\lipabyte\tools\export_listings_snapshot.php --live
```

## ElevenLabs steps (avoid errors)
1. Keep **Enable RAG = Every turn**
2. Upload/replace the files above in **Knowledge Base → Sources**
3. Wait until files finish processing
4. Open agent **System prompt** and replace with `AGENT_INSTRUCTIONS.txt`
5. **Publish** the agent if required
6. **Test RAG** with:
   - What is LipaByte?
   - How do Device Inquiries work?
   - How much is a laptop per day? (only if snapshot has laptops)
   - Who will be UB president in 2040? (must say unknown)
7. Hard-refresh the website widget and ask the same questions

## Do not
- Turn RAG off
- Upload before snapshot file exists
- Invent prices during testing if snapshot is missing an item
