#!/usr/bin/env bash
set -euo pipefail

required_variables=(AWS_REGION ADMIN_CIDR PROJECT_NAME PROJECT_ENVIRONMENT)
for variable in "${required_variables[@]}"; do
    if [[ -z "${!variable:-}" ]]; then
        echo "Thiếu biến môi trường bắt buộc: ${variable}" >&2
        exit 1
    fi
done

if ! command -v aws >/dev/null 2>&1; then
    echo "Chưa có AWS CLI. Cài và cấu hình AWS CLI ngoài repository trước khi chạy preflight." >&2
    exit 1
fi

identity="$(aws sts get-caller-identity --output json)"
echo "AWS identity: ${identity}"
echo "Region: ${AWS_REGION}"
echo "Project tag: ${PROJECT_NAME}"
echo "Environment tag: ${PROJECT_ENVIRONMENT}"
echo "Admin CIDR: ${ADMIN_CIDR}"

aws ec2 describe-availability-zones --region "${AWS_REGION}" --filters Name=state,Values=available --query 'AvailabilityZones[].ZoneName' --output text
echo "Preflight read-only thành công. Script này không tạo AWS resource."
