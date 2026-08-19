# PI Gate

PI Gate munkaido-nyilvantarto es mobil blokkolasi rendszer.

## Tartalom

- `server/` - Laravel alapu szerver API es admin felulet Docker kornyezettel.
- `android/` - Android PDA alkalmazas NFC blokkolashoz, fotoval, GPS-szel es offline szinkronnal.

## Biztonsag

A repository nem tartalmazhat eles `.env` fajlt, adatbazismentest, PDA tokent, build outputot vagy kulcsfajlt.

Eles kornyezetben a szerver beallitasait `.env` fajlbol kell megadni a `server/app/.env.example` alapjan.

## Szerver

```bash
cd server
docker compose up -d
```

A Laravel alkalmazas forrasa: `server/app`.

## Android

```bash
cd android
./gradlew assembleDebug
```

Windows alatt:

```powershell
cd android
.\gradlew.bat assembleDebug
```
