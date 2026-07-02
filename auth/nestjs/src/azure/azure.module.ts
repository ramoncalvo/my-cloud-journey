import { Module } from "@nestjs/common";

import { AzureClientService } from "./azure-client.service";
import { AzureController } from "./azure.controller";

@Module({
  controllers: [AzureController],
  providers: [AzureClientService],
})
export class AzureModule {}
