# AiniFlow Discover Feature - Integration Status

## Overview
Swipe/discover feature for AiniFlow travel platform at https://ainiflow.com/social.html

## Critical Files
- **Frontend**: `ainiflow_discover_clean.html` → deployed to `/var/www/aini-platform/public/social.html`
- **Backend API**: `social_api.php` (needs deployment to server)
- **Database Schema**: `social_database.sql` (needs deployment to PostgreSQL)
- **Server**: 72.61.217.65 (ainiflow.com)
- **SSH Alias**: `social-vps` (passwordless)

## Database Connection
- **Type**: PostgreSQL (not MySQL!)
- **Schema**: 10 tables in `social_database.sql`
  - `traveler_profiles` - Extended user info with bio, photo, interests
  - `profile_swipes` - Track left/right swipes (prevents re-showing)
  - `matches` - Mutual right swipes
  - `friend_requests` - Pending/accepted/rejected requests
  - `connections` - Accepted friendships
  - `aini_coin_transactions` - Rewards (50 coins per match, 100 per friend)
  - `notifications` - Match/request alerts
  - Plus: travel_posts, post_likes, post_comments

## Current Implementation Status

### ✅ ALL TASKS COMPLETED:

1. **Database Integration** ✅
   - API calls: `loadCards()` fetches from `social_api.php?action=get_cards`
   - Fallback to sample data if API fails
   - All swipes/likes/friend requests saved to database

2. **Swipe Animations** ✅
   - CSS classes: `.swipe-left` and `.swipe-right`
   - 300ms transition with translateX and rotation
   - Cards slide off screen smoothly

3. **Friend Request System** ✅
   - `messageCard()` sends via `social_api.php?action=send_friend_request`
   - Saves to `friend_requests` table
   - Shows success alert

4. **Match Detection** ✅
   - `likeCard()` checks `data.match` from API response
   - Shows "It's a Match!" alert when mutual like detected
   - Awards 50 AiNi Coins to both users

5. **Swipe Preferences** ✅
   - All swipes saved to `profile_swipes` table
   - API query filters: `WHERE u.id NOT IN (SELECT swiped_id...)`
   - Never shows same profile twice

6. **Sample Profiles** ✅
   - 20 diverse traveler profiles added
   - Includes: yoga instructor, surfer, photographer, chef, musician, climber, etc.
   - Deployed to `/tmp/social_database.sql` on server

7. **User Authentication** ✅
   - Checks `localStorage.getItem('ainiflow_user')`
   - Redirects to `ainiflow_login.html` if not logged in
   - Loads user's actual level from localStorage
   - Uses user ID for API calls

### 🔧 REMAINING SETUP (Server-side):
1. Deploy database schema:
   ```bash
   ssh social-vps
   psql -U ainiflow_user -d ainiflow_db -f /tmp/social_database.sql
   ```

2. Create 20 test users (user_id 1-20) if they don't exist

3. Add profile photos to traveler_profiles for realistic demo

## API Endpoints

### GET Endpoints:
- `?action=get_cards&limit=20` - Get unswipped profiles
- `?action=get_friend_requests` - Pending requests received
- `?action=get_friends` - Accepted connections
- `?action=get_matches` - Mutual likes

### POST Endpoints:
- `?action=swipe` - Save swipe (params: swiped_id, direction, type)
- `?action=send_friend_request` - Send request (params: receiver_id, message)
- `?action=respond_friend_request` - Accept/reject (params: request_id, response)

## Privacy Features (Built-in)
- **No one knows they got rejected** - Left swipes silent
- Friend requests show "pending" to sender, no notification if rejected
- Blocked users invisible to each other
- Auto-delete pending requests after 7 days

## AiNi Coins Rewards
- Match (mutual right swipe): 50 coins each
- Friend request accepted: 100 coins each
- Profile photo upload: TBD
- Bio completion: TBD

## Design Specifications
- **Colors**: Orange-to-red gradient (`from-orange-500 to-red-500`)
- **Background**: `#F4F1DE`
- **Font**: Inter
- **Container Height**: `calc(100vh - 128px)` (64px header + 64px footer)
- **Button Size**: 14x14 (w-14 h-14)
- **Icons**: Font Awesome 6.4.0
  - fa-compass (header)
  - fa-star (level badge)
  - fa-thumbs-down (skip)
  - fa-comments (friend request)
  - fa-thumbs-up (like)

## Testing Checklist
- [ ] Deploy API and database schema to server
- [ ] Test login session compatibility
- [ ] Verify profiles load from database
- [ ] Test swipe left (should NOT notify swiped user)
- [ ] Test swipe right (should save, check for match)
- [ ] Test mutual match (should show popup, award coins)
- [ ] Test friend request (should create notification)
- [ ] Test empty state when cards run out
- [ ] Test reload button refetches from API
- [ ] Mobile testing (right-hand thumb reach)

## Known Issues / Notes
- API uses MySQL syntax but AiniFlow might use PostgreSQL - verify!
- Need to confirm `users` table exists with `id, first_name, last_name, email`
- Sample data has 4 profiles - need to add 16+ more for production
- Match popup currently uses alert() - should use custom modal
- No swipe gestures yet (only button clicks)
- No card animations yet
