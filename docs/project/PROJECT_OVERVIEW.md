# PROJECT OVERVIEW

## 1. Project Title

**Xây dựng và triển khai hệ thống thương mại điện tử trên nền tảng AWS**

## 2. Project Context

Đây là project thuộc môn **Cloud Computing**.

Yêu cầu chính của đề tài là xây dựng một hệ thống và sử dụng nền tảng điện toán đám mây để triển khai hệ thống.

Nhóm lựa chọn:

> **Xây dựng và triển khai hệ thống thương mại điện tử trên nền tảng AWS.**

Project không chỉ yêu cầu xây dựng một website bán hàng mà còn cần thể hiện cách AWS được sử dụng để triển khai, lưu trữ, quản lý, bảo mật và giám sát hệ thống.

## 3. Problem Definition

Một hệ thống thương mại điện tử cần có khả năng:

- Cho phép người dùng truy cập thông qua Internet.
- Quản lý tài khoản người dùng.
- Hiển thị và quản lý sản phẩm.
- Tìm kiếm và xem thông tin sản phẩm.
- Quản lý giỏ hàng.
- Đặt hàng.
- Quản lý đơn hàng.
- Quản lý dữ liệu.
- Lưu trữ hình ảnh sản phẩm.
- Có khu vực quản trị dành cho Admin.

Nếu hệ thống chỉ chạy trên máy local thì:

- Người dùng bên ngoài không thể truy cập thuận tiện.
- Database phụ thuộc vào máy local.
- File và hình ảnh được lưu trực tiếp trên server.
- Khó triển khai và vận hành trên Internet.
- Khó theo dõi trạng thái hệ thống.
- Chưa thể hiện được kiến thức Cloud Computing.

Do đó, project sẽ xây dựng hệ thống thương mại điện tử và triển khai các thành phần phù hợp lên **Amazon Web Services (AWS)**.

## 4. Main Objective

Mục tiêu chính:

> **Xây dựng một hệ thống thương mại điện tử hoạt động hoàn chỉnh và triển khai hệ thống trên AWS.**

Project cần thể hiện được hai phần:

### Part 1 — Build the E-commerce System

Xây dựng các chức năng cần thiết của website thương mại điện tử.

### Part 2 — Deploy the System on AWS

Sử dụng các dịch vụ AWS phù hợp để triển khai và vận hành hệ thống.

Hai phần này phải được liên kết thành một hệ thống hoàn chỉnh.

## 5. Current System

Repository hiện tại đã có một hệ thống thương mại điện tử được phát triển bằng:

- PHP
- MySQL
- HTML
- CSS
- JavaScript

Cấu trúc hiện tại bao gồm các thành phần như:

```text
aws-ecommerce-platform/
├── public/
│   ├── admin/
│   ├── ajax/
│   ├── assets/
│   ├── account.php
│   ├── cart.php
│   ├── checkout.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── product_detail.php
│   ├── products.php
│   ├── register.php
│   ├── storage_file.php
│   └── success.php
├── app/
├── config/
├── database/
├── includes/
├── uploads/
├── storage/
├── tests/
├── deploy/
└── docs/
```

## 6. Existing Features

### Customer

- Register
- Login
- Logout
- View products
- View product details
- Search/filter products
- Shopping cart
- Checkout
- Place order
- Account management
- Order information

### Admin

- Admin dashboard
- Product management
- Category management
- Brand management
- Banner management
- Order management

### Database

Database hiện tại có các thành phần chính:

- Users
- Products
- Categories
- Brands
- Banners
- Orders
- Order details

## 7. Current Architecture

Hiện tại hệ thống chủ yếu được thiết kế để chạy local.

```text
User
  |
  v
PHP Application
  |
  v
Local MySQL Database
  |
  +---- Local Images / Uploads
```

Database hiện tại sử dụng kết nối local, ví dụ:

```text
DB_HOST = localhost
DB_NAME = webbanhang
```

Điều này phù hợp trong giai đoạn development nhưng chưa phải kiến trúc cuối cùng của project Cloud Computing.

## 8. Target System

Mục tiêu tiếp theo là chuyển hệ thống hiện tại thành:

```text
Local E-commerce Application
            |
            v
     Cloud-enabled System
            |
            v
           AWS
```

Kiến trúc tổng quát:

```text
                     USER
                       |
                       v
                   Internet
                       |
                       v
                AWS Environment
                       |
                 Web Application
                       |
             +---------+---------+
             |                   |
             v                   v
          Database             Storage
```

## 9. Proposed AWS Services

Các dịch vụ dưới đây là kiến trúc dự kiến và cần được đánh giá trước khi triển khai.

### Amazon EC2

Chạy PHP Web Application trên cloud server và thay thế việc chạy application trên localhost.

### Amazon RDS

Lưu MySQL database trên AWS.

Chuyển từ:

```text
localhost MySQL
```

sang:

```text
Amazon RDS MySQL
```

### Amazon S3

Lưu trữ:

- Product images
- User uploads
- Static files nếu cần

### Amazon CloudFront

Phân phối static content tới người dùng. Đây là thành phần có thể xem xét sau khi hệ thống cơ bản đã hoạt động.

### Amazon VPC

Quản lý network giữa các AWS resources.

Ví dụ:

```text
Internet
   |
   v
EC2
   |
   v
RDS
```

Database không nên được public nếu không cần thiết.

### Security Groups

Kiểm soát traffic được phép truy cập các AWS resources.

### AWS IAM

Quản lý quyền truy cập AWS resources.

Project không được hard-code AWS credentials vào source code.

### Amazon CloudWatch

Theo dõi:

- Application/server logs
- CPU
- Resource status
- Errors
- System health

## 10. Proposed AWS Architecture

```text
                         USER
                           |
                           v
                        Internet
                           |
                           v
                     CloudFront
                           |
                           v
                     +-----------+
                     |    EC2    |
                     | PHP App   |
                     +-----+-----+
                           |
              +------------+------------+
              |                         |
              v                         v
          +-------+                 +-------+
          |  RDS  |                 |  S3   |
          | MySQL |                 | Files |
          +-------+                 +-------+

                Supporting Services

                     IAM
                     VPC
               Security Groups
                  CloudWatch
```

Đây chưa phải kiến trúc cuối cùng. Architecture phải được review trước khi implementation.

## 11. 5W1H Analysis

### WHAT — What are we building?

Một hệ thống thương mại điện tử được xây dựng và triển khai trên AWS, bao gồm:

- Web application
- Authentication
- Product management
- Shopping cart
- Checkout
- Order management
- Admin management
- Database
- File/image storage
- Cloud deployment
- Security
- Monitoring

### WHY — Why AWS?

AWS được sử dụng để cung cấp cloud infrastructure cho hệ thống:

- Deploy application lên Internet.
- Sử dụng cloud server.
- Sử dụng managed database.
- Lưu trữ file.
- Quản lý network.
- Quản lý permissions.
- Monitor system.
- Tách application, database và storage thành các thành phần rõ ràng.

### WHO — Who uses the system?

**Customer:** xem/tìm kiếm sản phẩm, giỏ hàng, đặt hàng, theo dõi đơn hàng, quản lý tài khoản.

**Admin:** quản lý products, categories, brands, orders, banners và nội dung hệ thống.

**Development Team:** phát triển, triển khai, kiểm thử và quản lý hệ thống.

### WHERE — Where does the system run?

Development:

```text
Local Computer
```

Production/Cloud:

```text
AWS
```

Các thành phần dự kiến:

```text
Application -> EC2
Database -> RDS
Images/Files -> S3
Monitoring -> CloudWatch
```

### WHEN — When is AWS used?

```text
Develop
   |
   v
Test locally
   |
   v
Prepare for cloud
   |
   v
Deploy AWS infrastructure
   |
   v
Deploy application
   |
   v
Migrate database
   |
   v
Configure storage
   |
   v
Configure security
   |
   v
Monitoring
   |
   v
Final testing
```

### HOW — How does the system work?

```text
Customer
   |
   v
Website
   |
   v
PHP Application
   |
   +----------------+
   |                |
   v                v
Database          Storage
   |                |
   v                v
  RDS              S3
```

## 12. Current Problems to Review

Trước khi triển khai AWS cần review toàn bộ repository.

### Configuration

- Database configuration đang phụ thuộc localhost.
- Cần chuẩn bị environment variables.
- Không hard-code credentials.

### Database

- Review database relationships.
- Review constraints.
- Review order processing.
- Review stock management.

### Security

Kiểm tra:

- Authentication
- Authorization
- Password hashing
- SQL Injection
- XSS
- CSRF
- Session security
- Upload security
- Admin access

### Storage

Hiện tại một số file được lưu local. Cần đánh giá file nào nên chuyển sang S3.

### Deployment

Repository hiện tại cần được kiểm tra về khả năng chạy trên EC2.

### Monitoring

Cần xác định logs và metrics nào nên được theo dõi bằng CloudWatch.

## 13. Development Roadmap

### Phase 0 — Requirement & Repository Review

- Đọc yêu cầu project.
- Review repository.
- Review existing features.
- Review architecture.
- Review security.
- Review database.
- Xác định missing requirements.

Output:

```text
Current System Assessment
Gap Analysis
Risk Assessment
```

### Phase 1 — Application Preparation

- Environment configuration.
- Database configuration.
- Remove hard-coded configuration.
- Production configuration.
- Error handling.

### Phase 2 — AWS Architecture

Thiết kế architecture chính thức và xác định:

- EC2
- RDS
- S3
- VPC
- Security Groups
- IAM
- CloudWatch

Chỉ sử dụng những service thực sự cần thiết.

### Phase 3 — Database Migration

Chuyển:

```text
Local MySQL
```

sang:

```text
Amazon RDS MySQL
```

Verify:

- Schema
- Data
- Connection
- CRUD
- Orders

### Phase 4 — Application Deployment

Deploy PHP application lên EC2.

Kiểm tra:

- Website access
- PHP runtime
- Database connection
- Authentication
- Products
- Cart
- Checkout
- Admin

### Phase 5 — Storage Migration

Đánh giá và chuyển images/uploads phù hợp sang Amazon S3.

Kiểm tra:

- Upload
- Read
- Display
- Delete
- Permissions

### Phase 6 — Security

Configure:

- IAM
- Security Groups
- Database access
- Application secrets
- HTTPS nếu nằm trong scope
- File permissions

### Phase 7 — Monitoring

Configure CloudWatch để theo dõi:

- EC2 health
- CPU
- Logs
- Errors
- Availability

### Phase 8 — System Testing

Test end-to-end:

```text
Register
   |
Login
   |
Browse Products
   |
Add to Cart
   |
Checkout
   |
Create Order
   |
Database
   |
Admin manages Order
```

### Phase 9 — Documentation & Demo

Chuẩn bị:

- Architecture diagram
- Deployment workflow
- AWS service explanation
- Screenshots
- Testing results
- Security explanation
- Cost explanation
- Demo scenario
- Final report
- Presentation

## 14. Development Rules

Project phải tuân theo:

```text
Understand
   |
   v
Plan
   |
   v
Small Change
   |
   v
Run
   |
   v
Verify
   |
   v
Test
   |
   v
Refactor
   |
   v
Commit
```

Không thực hiện nhiều thay đổi lớn cùng lúc.

Mỗi thay đổi phải trả lời:

1. What is being changed?
2. Why is it necessary?
3. Which files are affected?
4. What problem does it solve?
5. How will it be tested?
6. What could break?

## 15. Rules for AI Agents

### MUST

- Đọc project overview trước.
- Đọc repository trước khi đề xuất implementation.
- Kiểm tra code hiện tại trước khi tạo code mới.
- Giữ architecture đơn giản.
- Ưu tiên tái sử dụng hệ thống hiện tại.
- Giải thích lý do trước các thay đổi lớn.
- Test sau mỗi phase.
- Báo cáo vấn đề security.
- Phân biệt local development và AWS production.

### MUST NOT

- Không rewrite toàn bộ project.
- Không tự đổi framework.
- Không tự thêm microservices.
- Không tự thêm Kubernetes.
- Không tự thêm AWS services không cần thiết.
- Không hard-code credentials.
- Không commit secrets.
- Không sửa architecture lớn khi chưa được approval.

## 16. Agent Initial Task

Khi Agent lần đầu đọc file này:

**KHÔNG CODE.**

Thực hiện:

```text
Read Requirements
        |
        v
Read Repository
        |
        v
Understand Current System
        |
        v
Review Existing Implementation
        |
        v
Find Problems
        |
        v
Identify Missing Requirements
        |
        v
Propose AWS Architecture
        |
        v
Create Migration Plan
        |
        v
STOP
        |
        v
WAIT FOR HUMAN APPROVAL
```

Output đầu tiên phải gồm:

1. Current System Assessment
2. Existing Features
3. Problems Found
4. Security Risks
5. Missing Requirements
6. AWS Architecture Recommendation
7. AWS Services Required
8. AWS Services Optional
9. Migration Plan
10. Testing Plan
11. Definition of Done

Sau đó **STOP và chờ approval trước khi implementation**.

## 17. Definition of Done

Project chỉ được coi là hoàn thành khi:

- E-commerce core functions hoạt động.
- Application chạy được trên AWS.
- Database hoạt động trên cloud.
- Application kết nối database thành công.
- Storage strategy hoạt động.
- Authentication hoạt động.
- Customer workflow hoạt động.
- Admin workflow hoạt động.
- Security configuration được kiểm tra.
- Monitoring hoạt động.
- Không có credentials/secrets trong repository.
- End-to-end testing PASS.
- AWS architecture được document.
- Deployment process được document.
- Demo scenario hoàn chỉnh.
- Final report hoàn chỉnh.

## 18. Final Project Goal

Kết quả cuối cùng không chỉ là:

> “Website bán hàng chạy được.”

Mà phải chứng minh:

> **Nhóm đã xây dựng một hệ thống thương mại điện tử và sử dụng AWS để triển khai, lưu trữ dữ liệu, quản lý tài nguyên, bảo mật và giám sát hệ thống theo kiến trúc Cloud Computing.**
