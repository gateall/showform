# BLOG AUTOMATION STAGE 8 REPORT

## Status
IMPLEMENTATION COMPLETE / DEPLOYMENT AND BROWSER QA PENDING

이 저장소는 여러 세션이 동시에 작업하는 공유 워킹 디렉터리입니다. 아래 내용 중 주간·월간
광고주·콘텐츠 성과 화면(`report_weekly.php`/`report_monthly_adv.php`/`report_performance.php`)과
V12 스키마 초안, `export_excel.php`의 최초 버전은 이 세션이 아니라 동시에 작업 중인 다른 세션의
결과물입니다(commit `55a3cb5`). 이 세션은 그 위에 **root-cause 버그 수정 + 성공·실패 통계 전용
화면 + 엑셀 보안 강화 + 관리자 메뉴 연결**을 추가했습니다(commit `f868f19`, 그리고 이 보고서를
작성하며 admin.menu360.php에 남은 메뉴 3건을 추가로 연결).

## Repository / Branch
E:\0000000_AI Enterprise Framework\PROJECTS\000_블로그자동화_WORKTREE / feature/blog-automation-mvp

## Base SHA / Commit SHA
57d81fc(이 세션이 직접 작업 시작한 시점) → **f868f19**(이 세션의 마지막 커밋, 이 보고서
커밋에서 admin.menu360.php 메뉴 3건 추가분 포함)

## 이번에 발견·수정한 실제 버그 (파일 존재 여부가 아니라 동작 실패의 진짜 원인)

1. **`blog_automation_v12.sql` 테이블명 불일치** — v1~v11은 전부 `{prefix}blog_테이블명`
   형식(install.php가 `{prefix}`를 실 접두어로 치환)인데 v12만 `g5_sf_blog_post_performance`처럼
   접두어 없이, 게다가 존재하지 않는 `sf_` 세그먼트까지 하드코딩되어 있었습니다. FK가 참조하는
   `g5_sf_blog_post_targets`가 애초에 존재하지 않아 설치 자체가 실패하는 구조였습니다.
   → `{prefix}blog_post_performance` / `{prefix}blog_report_snapshots`로 수정.
2. **`bp_table()` 허용 목록 누락** — `images`/`post_images`/`category_mappings`/
   `naver_packages`/`post_performance`/`report_snapshots` 전부 여러 파일에서 이미 호출되고
   있었지만 공통 라이브러리의 허용 목록에 없어 전부 "잘못된 테이블 요청입니다"로 막혀 있었습니다.
   이미지 라이브러리·네이버 패키지·카테고리 매핑·콘텐츠 성과 기능 전부가 이 한 가지 이유로
   동작하지 않고 있었습니다. → 6개 테이블명 추가.
3. **`report_weekly.php` 치명적 오류** — `bp_get_yoil()`가 조건부(`if (!function_exists())`)로
   정의되어 있었는데, 정의 위치가 파일 사용 지점보다 뒤에 있어 PHP가 함수를 끌어올려주지 않고
   "Call to undefined function" 오류가 나는 구조였습니다. → 정의를 사용 지점 앞으로 이동.
4. **`export_excel.php` 권한 검사 누락 + 수식 주입 미방지** — `auth_check_menu()` 호출이 아예
   없어 관리자 메뉴 권한과 무관하게 접근 가능했고, 셀 값에 `=`,`+`,`-`,`@`로 시작하는 사용자
   입력(포스트 제목 등)이 그대로 들어가 있었습니다. → 권한 검사 추가, 모든 브랜치에 수식 주입
   방지 이스케이프 적용.

## Changed Files (이 세션이 직접 수정/작성)
- adm/blog/sql/blog_automation_v12.sql (버그 수정)
- adm/blog/lib/blog_common.lib.php (bp_table 허용 목록 6건 추가)
- adm/blog/lib/blog_report.lib.php (오류 분류/플랫폼 통계/Job 확장 통계/주간 프리셋 추가)
- adm/blog/report_stats.php (신규 — 성공·실패 통계 전용 화면, Job/Attempt 구역 분리)
- adm/blog/export_excel.php (권한 검사·수식 주입 방지 추가, daily/site/stats 유형 추가)
- adm/blog/report_daily.php, report_site.php (엑셀 다운로드 버튼 연결)
- adm/blog/report_weekly.php (치명적 오류 수정만, 설계는 다른 세션 작업 그대로 유지)
- adm/admin.menu360.php (일간/사이트별/성공실패 통계 메뉴 3건 추가 — 기존 주간/월간/성과
  항목은 다른 세션이 이미 추가한 것을 그대로 유지)

## 다른 세션이 작업한 파일(이 세션은 버그 수정 외 손대지 않음)
- adm/blog/report_weekly.php (버그 수정 부분 제외 설계 원본)
- adm/blog/report_monthly_adv.php
- adm/blog/report_performance.php
- adm/css/admin_extend_sf_blog.css의 인쇄용 CSS 부분
- adm/blog/install.php의 V12 설치 흐름 배선
- adm/blog/cron/publish_scheduler.php (현재도 미커밋 상태로 수정 중 — 이 세션 스코프 아님,
  아래 "확인되었으나 손대지 않은 항목" 참조)

## Stage 8 기능별 현재 상태

| 기능 | 상태 | 비고 |
|---|---|---|
| 보고서 대시보드 | 완료 | 이 세션 구현, Docker 검증 완료 |
| 일간 발행 보고서 | 완료 | 이 세션 구현, Docker 검증 완료 |
| 주간 운영 보고서 | 완료(치명적 버그 수정 후) | 설계는 다른 세션, 이 세션이 크래시만 수정 |
| 월간 광고주 보고서 | 파일 존재·lint 통과 확인만 | 다른 세션 작업, 이 세션은 기능 검증 안 함 |
| 성공·실패 통계 | 완료(전용 화면) | 이 세션 구현·Docker 검증 — PM 지적사항 반영, 라이브러리에만
그치지 않고 report_stats.php 전용 화면 신설 |
| 사이트별 발행 통계 | 완료 | 이 세션 구현, Docker 검증 완료 |
| 콘텐츠별 성과 기록 | 파일 존재·lint 통과·bp_table 차단 해제 확인만 | 다른 세션 작업, 실제
데이터 입력·조회 기능 검증은 안 함 |
| Excel 내보내기 | 완료(단, 표현 정정 필요) | **HTML 표를 .xls로 저장하는 방식이며 실제
Office Open XML(.xlsx)이 아닙니다** — "Excel 호환 파일 내보내기"로 표현하는 것이 정확합니다.
권한 검사·수식 주입 방지는 이 세션에서 추가해 안전합니다. |
| PDF 내보내기 | 부분 완료(표현 정정 필요) | 전용 PDF 생성 라이브러리가 아니라 인쇄용 CSS +
브라우저 인쇄 기능입니다. "PDF 자동 생성"이 아니라 "인쇄용 보고서 및 PDF 저장 지원"이 정확한
표현입니다. |
| 보고서 생성 이력 | 테이블만 존재 | `report_snapshots` 테이블은 v12로 생성되나, 실제 저장·조회
기능(화면)은 아직 없습니다. |
| 자동 생성 일정 | 미구현 | `adm/blog/cron/publish_scheduler.php`는 예약 발행(publish_jobs)
폴러이지 보고서 스냅샷 자동 생성기가 아닙니다. 현재도 미커밋 수정이 진행 중인 것으로 보아
다른 세션이 이 부분을 작업 중일 가능성이 있습니다 — 이 세션은 이 파일에 손대지 않았습니다. |

## 확인되었으나 손대지 않은 항목
- `adm/blog/cron/publish_scheduler.php`: 3줄의 미커밋 변경 확인(`error_reporting(E_ALL)` 진단
  코드 추가 + `blog_publisher.lib.php` include 추가). 다른 세션이 진행 중인 작업으로 판단해
  이 세션은 커밋하거나 수정하지 않았습니다.
- 저장소 루트의 `check_schema.php`: 인증 없이 `SHOW CREATE TABLE`을 출력하는 디버그 스크립트
  (다른 세션 작성, 미추적 상태). 운영에 실수로 배포되면 스키마 정보 노출 위험이 있어 **커밋하지
  않고 삭제하시길 권장**하나, 다른 세션의 작업물이라 이 세션이 임의로 삭제하지 않았습니다.
- `temp_3_8.txt`/`temp_stage8.txt`: 과거 작업지시서 프롬프트를 저장해둔 메모 파일(다른 세션
  작성, 미추적). 기능에 영향 없어 그대로 두었습니다.

## Verification
- PHP 8.4 lint: 이 세션이 만들거나 수정한 9개 파일 전부 PASS
- UTF-8 no-BOM: 전부 확인
- Docker MariaDB 10.6: 격리 컨테이너에 v1~v12 재설치 → 종료 코드 0, `g5_blog_post_performance`/
  `g5_blog_report_snapshots` 정확한 이름으로 생성 확인. `bp_table()` 직접 호출로 이전에
  막혀 있던 6개 테이블명 전부 정상 반환, 잘못된 이름은 여전히 차단됨을 확인. 테스트 후 컨테이너
  삭제.
- §17.2 재시도 통계(작업 1건/시도 3회/최종성공 1건) 등 이전 라운드에서 이미 검증한 집계 로직은
  이번에도 라이브러리 함수를 그대로 재사용했으므로 재검증하지 않음(회귀 없음 — 함수 시그니처
  변경 없이 순수 추가만 했음을 diff로 확인).
- 브라우저 실화면(모바일/PC, 메뉴 노출, 필터, 다운로드 클릭 등): **NOT TESTED** — 운영 로그인
  세션 또는 실행 가능한 웹 환경이 이 세션에 없습니다(반복적으로 문서화된 한계). PASS로 보고하지
  않습니다.
- `report_monthly_adv.php`/`report_performance.php`의 실제 데이터 조회·저장 기능: **NOT
  TESTED** — 다른 세션의 코드이며 이 세션이 직접 작성하지 않아 기능 정확성을 검증하지 않았습니다
  (파일 존재·lint·bp_table 허용만 확인).

## Final Recommendation
IMPLEMENTATION COMPLETE(핵심 파일·DB·메뉴 연결 전부 존재하고 이 세션이 발견한 치명적 버그는
수정됨) / **DEPLOYMENT AND DATABASE QA PENDING** — 운영 DB에 V12 적용 전 반드시 스테이징에서
먼저 재현 테스트를 권장하며(이번 수정 전 스키마로 이미 시도했다면 실패했을 것입니다), 브라우저
실화면 검증과 월간/성과 화면의 기능 정확성 검증이 아직 남아 있어 Stage 8 COMPLETE 최종 승인은
보류를 권장합니다.
