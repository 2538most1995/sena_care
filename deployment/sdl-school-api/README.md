# SDL_school → SENA Care

This package adds three token-protected backend endpoints: GET teachers, GET groups, POST teacher-authenticate. It does not export passwords or password hashes. Existing student API tokens do not gain these permissions automatically.

1. Extract the package into a temporary folder under SDL_school.
2. In the hosting terminal, run `php install.php /absolute/path/to/httpdocs/SDL_school` from the extracted package. The CLI-only installer validates exact insertion points before writing and backs up modified files outside httpdocs in `sena-private-backups/`. It preserves unrelated source edits and refuses incompatible versions.
3. From `SDL_school/laravel-app`, run `php artisan route:clear`, then `php artisan route:list --path=integrations/student-data`. Check for students, teachers, groups and teacher-authenticate.
4. Run `php artisan system:create-student-api-token YOUR_ADMIN_USERNAME --district-code=sena --name=sena-care --sena-care --no-expiry`. Replace YOUR_ADMIN_USERNAME with an active admin or super_admin. Existing same-name clients require an intentional rotation using --replace. Keep the token private.
5. In local SENA Care, open /integrations/sdl as super_admin, paste the token, choose “ทดสอบและบันทึกการเชื่อมต่อ”, then “ดึงข้อมูลล่าสุด”.

Rollback: restore the three existing files from the private backup, remove only the new SenaCareTeacherController.php, and clear route cache. Revoke the issued client using `php artisan system:revoke-student-api-token` (see --help) if withdrawing integration access.

No database migration or source student edits are required by this package. Hosting must already run the current Laravel SDL_school Student Data API. Do not upload a local .env, a database, or sena_care/app/config/sdl.local.php to SDL_school.
