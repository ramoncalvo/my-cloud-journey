import { Module } from "@nestjs/common";

import { AwsClientService } from "./aws-client.service";
import { AwsController } from "./aws.controller";

@Module({
  controllers: [AwsController],
  providers: [AwsClientService],
})
export class AwsModule {}
