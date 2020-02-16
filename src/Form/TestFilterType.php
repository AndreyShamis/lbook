<?php

namespace App\Form;

use App\Entity\TestFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TestFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Filter Name',
                'help' => 'Provide name for new filter, you can add here suite name if relevant'
            ))
            ->add('suiteUuid', null, [
                'label' => 'Suite UUID',
                'help' => 'Suite UUID or "*" for ALL suites'
            ])
            ->add('testList',TextareaType::class, [
                'attr' => array('class' => 'filterTestList', 'rows' => '10'),
                'label' => 'Test/s',
                'help' => 'Format like "tests/folder1/folder2/control.xxx" "," NEW_LINE separated values'
            ])
            ->add('testingLevel', ChoiceType::class, [
                'choices' => [
                    '*', 'SANITY', 'INTEGRATION', 'NIGHTLY', 'WEEKLY'],
                'choice_label' => function(string $testing_level, $key, $value) {
                    return strtoupper($testing_level);
                },
//                'choice_attr' => function((string $testing_level, $key, $value) {
//                    return ['class' => 'testing_level__'.strtolower($testing_level)];
//                },
//                'group_by' => function(Category $category, $key, $value) {
//                    // randomly assign things into 2 groups
//                    return rand(0, 1) == 1 ? 'Group A' : 'Group B';
//                },
                'preferred_choices' => function(string $testing_level, $key, $value) {
                    return $testing_level === '*';
                },
            ])

            ->add('projectName', null, [
                'label' => 'Project Name',
                'help' => 'Gerrit project name like company/myproject'
            ])
            ->add('cluster', null, [
                'label' => 'Cluster',
                'help' => 'Cluster name, example: "cluster_lbk"'
            ])
            ->add('chip', null, [
                'required' => false
            ])
            ->add('platform', null, [
                'required' => false
            ])
            ->add('executionMode', ChoiceType::class, [
                'choices' => [
                    '*', 'regular_mode', 'package_mode'],
                'label' => 'Package Mode',
                'choice_label' => function(string $executionMode, $key, $value) {
                    return $executionMode;
                },
                'preferred_choices' => function(string $executionMode, $key, $value) {
                    return $executionMode === '*';
                },
            ])
            ->add('branchName', null, [
                'required' => false,
                'help' => '[Branch Name (GERRIT_BRANCH) in PRE and MANIFEST_REVISION]']
            )
            ->add('enabled')
            ->add('description', null, [
                'required' => true,
                'label' => 'Description',
                'help' => 'Provide description for this filter, this message printed with disabled message in suite'
            ])
            ->add('defectUrl')
//            ->add('createdAt')
//            ->add('updatedAt')
            ->add('issueContact')
//            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestFilter::class,
        ]);
    }
}
