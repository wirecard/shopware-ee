<?xml version="1.0" encoding="utf-8"?>
<!--
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
-->
<menu xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/shopware/5.3/engine/Shopware/Components/Plugin/schema/menu.xsd">
    <entries>
        <entry>
            <name>Wirecard</name>
            @forlang
            <label lang="{{ lang }}">{{ strings.heading_title }}</label>
            @endforlang
            <class>sprite--wirecard</class>
            <parent identifiedBy="controller">Payments</parent>
            <children>
                <entry>
                    <name>WirecardElasticEngineTransactions</name>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_list }}</label>
                    @endforlang
                    <controller>WirecardElasticEngineTransactions</controller>
                    <action>index</action>
                    <class>sprite-money</class>
                </entry>
                <entry>
                    <name>WirecardElasticEngineLiveChat</name>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_support_chat }}</label>
                    @endforlang
                    <controller>WirecardElasticEngineTransactions</controller>
                    <action>livechat</action>
                    <class>sprite-balloons-box</class>
                    <onclick>
                        <![CDATA[
                            wirecardeeChatOpen();
                        ]]>
                    </onclick>
                </entry>
                <entry>
                    <name>WirecardElasticEngineEmailSupport</name>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.heading_title_support }}</label>
                    @endforlang
                    <controller>WirecardElasticEngineTransactions</controller>
                    <action>mailSupport</action>
                    <class>sprite-mail-send</class>
                </entry>
            </children>
        </entry>
    </entries>
</menu>
