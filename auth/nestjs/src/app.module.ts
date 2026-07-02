import { Module } from "@nestjs/common";

import { AppController } from "./app.controller";
import { AwsModule } from "./aws/aws.module";
import { AzureModule } from "./azure/azure.module";
import { GcpModule } from "./gcp/gcp.module";

@Module({
  imports: [AwsModule, AzureModule, GcpModule],
  controllers: [AppController],
})
export class AppModule {}
